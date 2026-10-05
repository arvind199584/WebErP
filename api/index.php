<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../modules/Superadmin/Users/Services/UserService.php';

use App\Core\Database;
use App\Modules\Superadmin\Users\Services\UserService;

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

// Initialize DB connection
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e.getMessage()]);
    exit;
}

// Request body JSON parsing
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($_POST)) {
    $input = array_merge($input, $_POST);
}

switch ($action) {
    case 'login':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            exit;
        }

        $userService = new UserService();
        $user = $userService->authenticate($username, $password);

        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsrname(),
                    'firstName' => $user->getFirstname(),
                    'lastName' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'officeId' => $user->getOfficeid()
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid username or password']);
        }
        break;

    case 'dashboard':
        // Fetch high level system stats from Neon DB
        try {
            $stats = [];
            
            $stmt = $db->query("SELECT COUNT(*) FROM employees");
            $stats['employees'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM office");
            $stats['offices'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM agreements");
            $stats['agreements'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM bills");
            $stats['bills'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM budget");
            $stats['budgets'] = (int)$stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $stats['users'] = (int)$stmt->fetchColumn();

            // Recent activity log
            $stmt = $db->query("SELECT id, table_name, action, changed_at FROM activity_logs ORDER BY changed_at DESC LIMIT 5");
            $stats['recentActivity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'finance':
        // Budget & Bills summary
        try {
            $budgets = $db->query("SELECT b.id, o.name as office_name, b.head, b.sub_head, b.allocated_amount, b.financial_year FROM budget b LEFT JOIN office o ON b.officeid = o.id ORDER BY b.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            $bills = $db->query("SELECT bi.id, o.name as office_name, bi.bill_no, bi.bill_date, bi.net_amount, bi.status FROM bills bi LEFT JOIN office o ON bi.officeid = o.id ORDER BY bi.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => [
                    'budgets' => $budgets,
                    'bills' => $bills
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'hr':
        // Employees & Attendance summary
        try {
            $employees = $db->query("SELECT e.id, o.name as office_name, e.full_name, e.designation, e.mobile, e.status FROM employees e LEFT JOIN office o ON e.officeid = o.id ORDER BY e.full_name ASC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
            $attendanceCount = $db->query("SELECT COUNT(*) FROM attendance_records WHERE attendance_date = CURRENT_DATE")->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'employees' => $employees,
                    'todayAttendanceCount' => (int)$attendanceCount
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'works':
        // Agreements, Work Orders, Supply Orders
        try {
            $agreements = $db->query("SELECT a.id, a.agreement_no, ag.name as agency_name, a.tendered_amount, a.status FROM agreements a LEFT JOIN agencies ag ON a.agency_id = ag.id ORDER BY a.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            $workOrders = $db->query("SELECT wo.id, wo.work_order_no, wo.amount, wo.issue_date, wo.status FROM work_orders wo ORDER BY wo.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            $supplyOrders = $db->query("SELECT so.id, so.order_no, so.total_amount, so.issue_date, so.status FROM supply_orders so ORDER BY so.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => [
                    'agreements' => $agreements,
                    'workOrders' => $workOrders,
                    'supplyOrders' => $supplyOrders
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'admin':
        // Offices & Agencies
        try {
            $offices = $db->query("SELECT id, name, location, code FROM office ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $agencies = $db->query("SELECT id, name, contact_person, email, gst_no FROM agencies ORDER BY name ASC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => [
                    'offices' => $offices,
                    'agencies' => $agencies
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action specified']);
        break;
}
