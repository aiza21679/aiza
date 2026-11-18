<?php
header('Content-Type: application/json');

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eboto_db";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Validate 'org' parameter
if (!isset($_GET['org']) || !is_numeric($_GET['org'])) {
    echo json_encode(['error' => 'Invalid organization ID']);
    exit;
}

$orgId = (int)$_GET['org'];

// Fetch Organization Info
$orgQuery = $conn->prepare("
    SELECT Org_ID, Org_Name, Description, Logo_Url 
    FROM student_organization 
    WHERE Org_ID = ?
");
$orgQuery->bind_param("i", $orgId);
$orgQuery->execute();
$orgResult = $orgQuery->get_result();

if ($orgResult->num_rows === 0) {
    echo json_encode(['error' => 'Organization not found']);
    exit;
}

$orgData = $orgResult->fetch_assoc();

// Fetch all organizations first (for mapping IDs → names)
$orgMap = [];
$orgsResult = $conn->query("SELECT Org_ID, Org_Name FROM student_organization");
while ($row = $orgsResult->fetch_assoc()) {
    $orgMap[(int)$row['Org_ID']] = $row['Org_Name'];
}

// Fetch Voters
$sql = "
    SELECT 
        s.Student_ID,
        s.Name,
        s.Email,
        s.Course,
        s.Year_Level,
        s.Status,
        GROUP_CONCAT(DISTINCT som2.Org_ID ORDER BY som2.Org_ID SEPARATOR ',') AS Org_IDs
    FROM student s
    INNER JOIN student_organization_members som_filter 
        ON s.Student_ID = som_filter.Student_ID
    LEFT JOIN student_organization_members som2 
        ON s.Student_ID = som2.Student_ID
    WHERE som_filter.Org_ID = ?
    GROUP BY s.Student_ID
    ORDER BY s.Name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orgId);
$stmt->execute();
$result = $stmt->get_result();

$voters = [];
while ($row = $result->fetch_assoc()) {
    // Split name (handle multi-part)
    $nameParts = preg_split('/\s+/', trim($row['Name']));
    $firstName = $nameParts[0] ?? '';
    $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

    // Convert Org_IDs string to array of names
    $orgIds = array_filter(explode(',', $row['Org_IDs'] ?? ''));
    $orgNames = array_map(function($id) use ($orgMap) {
        return $orgMap[(int)$id] ?? 'Unknown Organization';
    }, $orgIds);

    $voters[] = [
        'Student_ID' => $row['Student_ID'],
        'firstName' => $firstName,
        'lastName' => $lastName,
        'Email' => $row['Email'],
        'Course' => $row['Course'],
        'Year_Level' => $row['Year_Level'],
        'status' => $row['Status'] ?: 'Active',
        'organizations' => $orgNames // ✅ Now names, not IDs
    ];
}

// Return JSON
echo json_encode([
    'org_id' => $orgData['Org_ID'],
    'org_name' => $orgData['Org_Name'],
    'description' => $orgData['Description'],
    'logo_url' => $orgData['Logo_Url'],
    'voters' => $voters
], JSON_PRETTY_PRINT);

$conn->close();
?>
