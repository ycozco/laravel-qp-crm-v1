<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php patch-host-app.php /path/to/laravel-app\n");
    exit(1);
}

$appPath = rtrim($argv[1], '/');
$userModelPath = $appPath.'/app/Models/User.php';
$bootstrapPath = $appPath.'/bootstrap/app.php';
$corsPath = $appPath.'/config/cors.php';

if (! is_file($userModelPath)) {
    fwrite(STDERR, "User model not found at {$userModelPath}\n");
    exit(1);
}

$userModel = file_get_contents($userModelPath);
if ($userModel === false) {
    fwrite(STDERR, "Unable to read {$userModelPath}\n");
    exit(1);
}

$imports = [
    'Spatie\\Permission\\Traits\\HasRoles',
    'VentureDrake\\LaravelCrm\\Traits\\HasCrmAccess',
    'VentureDrake\\LaravelCrm\\Traits\\HasCrmTeams',
];

foreach ($imports as $import) {
    if (! str_contains($userModel, "use {$import};")) {
        $userModel = preg_replace(
            '/^namespace\s+App\\\\Models;\n/m',
            "namespace App\\Models;\n\nuse {$import};\n",
            $userModel,
            1
        );
    }
}

$classPos = strpos($userModel, 'class User extends Authenticatable');
if ($classPos === false) {
    fwrite(STDERR, "Unable to locate User class declaration\n");
    exit(1);
}

$bracePos = strpos($userModel, '{', $classPos);
if ($bracePos === false) {
    fwrite(STDERR, "Unable to locate opening brace for User class\n");
    exit(1);
}

$traitUsePos = strpos($userModel, 'use ', $bracePos);
$insertedTraits = ['HasRoles', 'HasCrmAccess', 'HasCrmTeams'];

if ($traitUsePos !== false) {
    $traitLineEnd = strpos($userModel, ';', $traitUsePos);
    if ($traitLineEnd === false) {
        fwrite(STDERR, "Unable to locate trait use terminator in User model\n");
        exit(1);
    }

    $traitSegment = substr($userModel, $traitUsePos, $traitLineEnd - $traitUsePos + 1);
    if (preg_match('/use\s+(.+);/s', $traitSegment, $match)) {
        $traits = array_map('trim', explode(',', preg_replace('/\s+/', ' ', trim($match[1]))));
        foreach ($insertedTraits as $trait) {
            if (! in_array($trait, $traits, true)) {
                $traits[] = $trait;
            }
        }

        $replacement = 'use '.implode(', ', $traits).';';
        $userModel = substr_replace($userModel, $replacement, $traitUsePos, strlen($traitSegment));
    } else {
        fwrite(STDERR, "Unable to parse trait block in User model\n");
        exit(1);
    }
} else {
    $replacement = "\n    use ".implode(', ', $insertedTraits).";\n";
    $userModel = substr_replace($userModel, $replacement, $bracePos + 1, 0);
}

file_put_contents($userModelPath, $userModel);

$corsConfig = <<<'PHP'
<?php

return [
    'paths' => [
        'api/*',
        'auth/*',
        'crm/api/*',
        'webhooks/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        static fn (string $origin): string => trim($origin),
        explode(',', env('CORS_ALLOWED_ORIGINS', 'https://crm1.qpsecure.cloud,https://crmapi.qpsecure.cloud,https://crm1app.qpsecure.cloud,https://admincrm1.qpsecure.cloud'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-CSRF-TOKEN',
        'X-Requested-With',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
PHP;

file_put_contents($corsPath, $corsConfig.PHP_EOL);

if (is_file($bootstrapPath)) {
    $bootstrap = file_get_contents($bootstrapPath);
    if ($bootstrap === false) {
        fwrite(STDERR, "Unable to read {$bootstrapPath}\n");
        exit(1);
    }

    if (! str_contains($bootstrap, "trustProxies(at: '*')")) {
        $updatedBootstrap = preg_replace(
            '/->withMiddleware\(function \(Middleware \$middleware\): void \{\n/',
            "->withMiddleware(function (Middleware \$middleware): void {\n        \$middleware->trustProxies(at: '*');\n",
            $bootstrap,
            1
        );

        if (is_string($updatedBootstrap)) {
            $bootstrap = $updatedBootstrap;
        }
    }

    file_put_contents($bootstrapPath, $bootstrap);
}
