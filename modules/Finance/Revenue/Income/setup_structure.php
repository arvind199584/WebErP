<?php
$baseDir = __DIR__;
$modules = [
    'Rates',
    'Membership',
    'TempMembership',
    'Visitor',
    'Coaching',
    'PaynPlay',
    'GroundBooking',
    'SwimmingPool',
    'Electricity'
];

foreach ($modules as $mod) {
    $modDir = $baseDir . '/' . $mod;

    // Create Directories
    if (!is_dir($modDir)) mkdir($modDir, 0777, true);
    if (!is_dir($modDir . '/Controller')) mkdir($modDir . '/Controller', 0777, true);
    if (!is_dir($modDir . '/Services')) mkdir($modDir . '/Services', 0777, true);
    if (!is_dir($modDir . '/Views')) mkdir($modDir . '/Views', 0777, true);
    if (!is_dir($modDir . '/sql')) mkdir($modDir . '/sql', 0777, true);

    // Create Placeholder Controller
    $controllerContent = "<?php\ndeclare(strict_types=1);\nnamespace App\Modules\Finance\Revenue\Income;\n\nuse App\Core\BaseController;\n\nclass {$mod}Controller extends BaseController {\n    public function __construct() {\n        parent::__construct('Income');\n    }\n    public function handleRequest(): void {\n        echo '<h1>$mod Module</h1><p>Coming Soon...</p>';\n    }\n}";
    file_put_contents($modDir . "/Controller/{$mod}Controller.php", $controllerContent);

    // Create Placeholder Service
    $serviceContent = "<?php\ndeclare(strict_types=1);\nnamespace App\Modules\Finance\Revenue\Income;\n\nclass {$mod}Service {\n    // Logic goes here\n}";
    file_put_contents($modDir . "/Services/{$mod}Service.php", $serviceContent);

    // Create Placeholder View
    $viewContent = "<h1>$mod Module</h1>\n<p>This module is under construction.</p>";
    file_put_contents($modDir . "/Views/index.php", $viewContent);

    // Create Placeholder SQL
    $sqlContent = "-- Schema for $mod\n-- TODO: Define tables";
    file_put_contents($modDir . "/sql/schema.sql", $sqlContent);
}

echo "Income sub-modules structure created successfully.";
?>
