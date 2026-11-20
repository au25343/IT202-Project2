
<?php
session_start();

// Database configuration
require_once dirname(__DIR__, 2) . "/private_db/db_config.php";

// Connect to MySQL
try {
    $conn = new mysqli(
        $db_host,
        $db_user,
        $db_password,
        $db_name,
        3306
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    die("Database connection failed.");
}

// Only accept form submissions
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Please submit the login form first.");
}

// Get form values
$firstName = trim($_POST["firstName"] ?? "");
$lastName = trim($_POST["lastName"] ?? "");
$password = $_POST["password"] ?? "";
$repID = trim($_POST["employeeID"] ?? $_POST["employeeId"] ?? "");
$phone = trim($_POST["phoneNumber"] ?? "");
$email = trim($_POST["email"] ?? "");
$transaction = trim($_POST["transaction"] ?? "");
$emailConfirm = isset($_POST["emailConfirm"]);

// Check required fields
if (
    $firstName === "" ||
    $lastName === "" ||
    $password === "" ||
    $repID === "" ||
    $phone === "" ||
    $email === "" ||
    $transaction === ""
) {
    die("
    Missing fields: " .
    "First Name: " . ($firstName === "" ? "MISSING | " : "OK | ") .
    "Last Name: " . ($lastName === "" ? "MISSING | " : "OK | ") .
    "Password: " . ($password === "" ? "MISSING | " : "OK | ") .
    "Employee ID: " . ($repID === "" ? "MISSING | " : "OK | ") .
    "Phone: " . ($phone === "" ? "MISSING | " : "OK | ") .
    "Email: " . ($email === "" ? "MISSING | " : "OK | ") .
    "Transaction: " . ($transaction === "" ? "MISSING" : "OK")
    );
}

// Check email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

// Check representative ID
if (!ctype_digit($repID)) {
    die("Invalid employee ID.");
}

// Verify representative
$sql = "SELECT RepID, FirstName, LastName,
               RepPassword, PhoneNumber, Email
        FROM Rep
        WHERE RepID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $repID);
$stmt->execute();

$result = $stmt->get_result();
$rep = $result->fetch_assoc();

$stmt->close();

if (!$rep) {
    die("Representative not found.");
}

// Compare login credentials
if (
    strcasecmp($firstName, $rep["FirstName"]) !== 0 ||
    strcasecmp($lastName, $rep["LastName"]) !== 0 ||
    $password !== $rep["RepPassword"] ||
    $phone !== $rep["PhoneNumber"]
) {
    die("Invalid representative credentials.");
}

// Verify email if confirmation is requested
if ($emailConfirm &&
    strcasecmp($email, $rep["Email"]) !== 0) {
    die("Email does not match our records.");
}

// Save login session
session_regenerate_id(true);

$_SESSION["repID"] = $rep["RepID"];
$_SESSION["repName"] =
    $rep["FirstName"] . " " . $rep["LastName"];

// Escape output
function clean($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Moving On Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 40px;
        }

        .container {
            background: white;
            max-width: 700px;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
        }

        h1 {
            color: #273b5a;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            color: #684b9a;
        }
    </style>
</head>
<body>

<div class="container">

<h1>Moving On Up</h1>

<h2>Login Successful</h2>

<p>Employee ID:
    <?php echo clean($rep["RepID"]); ?>
</p>

<p>First Name:
    <?php echo clean($rep["FirstName"]); ?>
</p>

<p>Last Name:
    <?php echo clean($rep["LastName"]); ?>
</p>

<p>Transaction Selected:
    <?php echo clean($transaction); ?>
</p>

<?php
if ($transaction === "search") {

    echo "<h2>Customer Moving Accounts</h2>";

    $sql = "SELECT
                m.MovingID,
                c.FirstName,
                c.LastName,
                m.MovingDate,
                m.ToCity,
                m.ToState,
                m.TotalWeight,
                m.Miles,
                m.TotalCost
            FROM Move m
            JOIN Client c
                ON m.ClientID = c.ClientID
            WHERE m.RepID = ?
            ORDER BY m.MovingDate";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $repID);
    $stmt->execute();

    $moves = $stmt->get_result();

    if ($moves->num_rows > 0) {

        while ($move = $moves->fetch_assoc()) {

            echo "<hr>";

            echo "<p>Moving ID: " .
                clean($move["MovingID"]) . "</p>";

            echo "<p>Client: " .
                clean($move["FirstName"]) . " " .
                clean($move["LastName"]) . "</p>";

            echo "<p>Moving Date: " .
                clean($move["MovingDate"]) . "</p>";

            echo "<p>Destination: " .
                clean($move["ToCity"]) . ", " .
                clean($move["ToState"]) . "</p>";

            echo "<p>Total Weight: " .
                clean($move["TotalWeight"]) . "</p>";

            echo "<p>Miles: " .
                clean($move["Miles"]) . "</p>";

            echo "<p>Total Cost: $" .
                clean($move["TotalCost"]) . "</p>";
        }

    } else {
        echo "<p>No moving accounts found.</p>";
    }

    $stmt->close();

} elseif ($transaction === "book") {

    echo "<h2>Book a Client's Moving Event</h2>";
    echo "<p>Representative verified successfully.</p>";
    echo "<a href='book_move.php'>
            Continue to Book Moving Event
          </a>";

} elseif ($transaction === "cancel") {

    echo "<h2>Cancel a Customer's Moving Event</h2>";
    echo "<p>Representative verified successfully.</p>";
    echo "<a href='cancel_move.php'>
            Continue to Cancel Moving Event
          </a>";

} elseif ($transaction === "request") {

    echo "<h2>Request Moving Services</h2>";
    echo "<p>This transaction is not implemented yet.</p>";

} elseif ($transaction === "additional") {

    echo "<h2>Request Additional Moving Services</h2>";
    echo "<p>This transaction is not implemented yet.</p>";

} elseif ($transaction === "create") {

    echo "<h2>Create a New Customer Account</h2>";
    echo "<p><a href="create_client.php">Click Here to Create Customer Account</a></p>";

} else {

    echo "<p>Invalid transaction selected.</p>";
}
?>

<p>
    <a href="index.html">Return to Login</a>
</p>

</div>

</body>
</html>

<?php
$conn->close();
?>
