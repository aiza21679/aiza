<?php
// ../php/fetchOrganizations.php
header('Content-Type: application/json; charset=utf-8');
$dbHost = 'localhost'; $dbUser = 'root'; $dbPass = ''; $dbName = 'eboto_db';
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) { echo json_encode([]); exit; }
$conn->set_charset('utf8mb4');

$sql = "
    SELECT 
        so.Org_ID,
        so.Org_Name,
        so.Description,
        so.Email,
        so.Logo_Url,
        COUNT(DISTINCT som.student_id) AS total_voters,
        SUM(CASE WHEN LOWER(COALESCE(s.status,'')) = 'pending' THEN 1 ELSE 0 END) AS pending_voters
    FROM student_organization AS so
    LEFT JOIN student_organization_members AS som ON so.Org_ID = som.org_id
    LEFT JOIN student AS s ON som.student_id = s.Student_ID
    GROUP BY so.Org_ID, so.Org_Name, so.Description, so.Email, so.Logo_Url
    ORDER BY so.Org_Name ASC
";
$result = $conn->query($sql);
$arr = [];
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $arr[] = [
            'Org_ID' => $r['Org_ID'],
            'Org_Name' => $r['Org_Name'],
            'Description' => $r['Description'],
            'Email' => $r['Email'],
            'Logo_Url' => $r['Logo_Url'] ?: '../assets/default-logo.jpg',
            'Total_Voters' => (int)$r['total_voters'],
            'Pending' => (int)$r['pending_voters']
        ];
    }
}
echo json_encode($arr);
$conn->close();
