
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['repID'])) {
    header("Location: index.html");
    exit;
}

// Database configuration
$configFile = dirname(__DIR__, 2) . "/private_db/db_config.php";

if (!is_readable($configFile)) {
    die("Configuration file cannot be read.");
}

require_once $configFile;

try {
    $con = mysqli_connect(
        $db_host,
        $db_user,
        $db_password,
        $db_name,
        3306
    );

    mysqli_set_charset($con, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    die("Database connection failed.");
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$repID = $_SESSION['repID'];
$message = "";
$clientName = "";
$clientID = "";
$verified = false;
$movingID = null;
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $clientID = trim($_POST['clientID'] ?? '');

    // STEP 1: VERIFY CLIENT
    if ($action === 'verify') {

        $sql = "SELECT FirstName, LastName
                FROM Client
                WHERE ClientID = ?";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $clientID);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $verified = true;
            $clientName = $row['FirstName'] . " " . $row['LastName'];
            $message = "Client verified successfully.";
        } else {
            $message = "Client does not exist.";
        }

        mysqli_stmt_close($stmt);
    }

    // STEP 2: BOOK MOVING EVENT
    elseif ($action === 'book') {

        $streetNumber = trim($_POST['streetNumber'] ?? '');
        $streetName = trim($_POST['streetName'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = strtoupper(trim($_POST['state'] ?? ''));
        $zipCode = trim($_POST['zipCode'] ?? '');
        $movingDate = trim($_POST['movingDate'] ?? '');
        $weight = trim($_POST['weight'] ?? '');
        $miles = trim($_POST['miles'] ?? '');
        $cost = trim($_POST['cost'] ?? '');

        // Verify client again
        $stmt = mysqli_prepare(
            $con,
            "SELECT FirstName, LastName
             FROM Client
             WHERE ClientID = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $clientID);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $client = mysqli_fetch_assoc($result);
        $clientExists = ($client !== null);

        mysqli_stmt_close($stmt);

        if ($clientExists) {
            $clientName = $client['FirstName'] . " " . $client['LastName'];
        }

        $date = DateTime::createFromFormat('!Y-m-d', $movingDate);
        $validDate = $date &&
                     $date->format('Y-m-d') === $movingDate;

        if (!$clientExists) {
            $message = "Client does not exist.";
        }
        elseif (
            $streetNumber === '' ||
            $streetName === '' ||
            $city === '' ||
            !preg_match('/^[A-Z]{2}$/', $state) ||
            !preg_match('/^\d{5}(-\d{4})?$/', $zipCode) ||
            !$validDate ||
            $movingDate < date('Y-m-d') ||
            !is_numeric($weight) ||
            !is_numeric($miles) ||
            !is_numeric($cost) ||
            (float)$weight <= 0 ||
            (float)$miles < 0 ||
            (float)$cost < 0 ||
            (float)$weight > 99999999.99 ||
            (float)$miles > 999999.99 ||
            (float)$cost > 99999999.99
        ) {
            $message = "Invalid information. Please check all fields.";
            $verified = true;
        }
        else {

            // Generate a unique Moving ID
            for ($attempt = 0; $attempt < 10; $attempt++) {

                $movingID = random_int(100000, 999999);

                $check = mysqli_prepare(
                    $con,
                    "SELECT MovingID FROM Move WHERE MovingID = ?"
                );

                mysqli_stmt_bind_param($check, "i", $movingID);
                mysqli_stmt_execute($check);

                $checkResult = mysqli_stmt_get_result($check);
                $exists = mysqli_num_rows($checkResult) > 0;

                mysqli_stmt_close($check);

                if ($exists) {
                    continue;
                }

                $sql = "INSERT INTO Move
                    (MovingID, ClientID, RepID,
                     ToStreetNumber, ToStreetName,
                     ToCity, ToState, ToZipCode,
                     MovingDate, TotalWeight, Miles, TotalCost)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($con, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "issssssssddd",
                    $movingID,
                    $clientID,
                    $repID,
                    $streetNumber,
                    $streetName,
                    $city,
                    $state,
                    $zipCode,
                    $movingDate,
                    $weight,
                    $miles,
                    $cost
                );

                try {
                    $saved = mysqli_stmt_execute($stmt);
                } catch (mysqli_sql_exception $e) {
                    error_log($e->getMessage());
                    $saved = false;
                }

                mysqli_stmt_close($stmt);

                if ($saved) {
                    break;
                }
            }

            if ($saved) {
                $message = "Moving event booked successfully!";
            } else {
                $movingID = null;
                $verified = true;
                $message = "Booking failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book Moving Event</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
}

nav {
    background: #253858;
    padding: 15px;
}

nav a {
    color: white;
    margin-right: 20px;
    text-decoration: none;
}

.container {
    background: white;
    max-width: 550px;
    margin: 35px auto;
    padding: 25px;
    border-radius: 8px;
}

label {
    display: block;
    margin-top: 12px;
}

input {
    width: 100%;
    box-sizing: border-box;
    padding: 9px;
    margin-top: 5px;
}

button {
    background: #253858;
    color: white;
    padding: 12px;
    border: none;
    margin-top: 18px;
    cursor: pointer;
}

.message {
    font-weight: bold;
}
</style>

<script>
function confirmBooking() {
    return confirm("Do you want to book this moving event?");
}
</script>
</head>

<body>

<nav>
    <a href="index.html">Home / Login</a>
    <a href="book_move.php">Book Moving Event</a>
</nav>

<div class="container">

<h2>Moving On Up</h2>
<h3>Book a Client's Moving Event</h3>

<p>
Representative ID:
<?php echo clean($repID); ?>
</p>

<?php if ($message !== ''): ?>

<p class="message">
<?php echo clean($message); ?>
</p>

<?php endif; ?>

<?php if ($movingID !== null && $saved): ?>

<h3>Booking Confirmed</h3>

<p>
Moving ID:
<strong><?php echo clean($movingID); ?></strong>
</p>

<script>
alert("Moving event booked successfully! Moving ID: <?php echo (int)$movingID; ?>");

if (confirm("Does the client have Special Instructions?")) {
    window.location.href =
        "special_instructions.php?movingID=<?php echo (int)$movingID; ?>";
}
</script>

<p>
<a href="book_move.php">Book Another Moving Event</a>
</p>

<?php elseif ($verified): ?>

<p>
Client:
<strong><?php echo clean($clientName); ?></strong>
</p>

<form method="POST" onsubmit="return confirmBooking();">

<input type="hidden" name="action" value="book">

<input type="hidden" name="clientID"
       value="<?php echo clean($clientID); ?>">

<label>Destination Street Number</label>
<input type="text" name="streetNumber"
       maxlength="10" required>

<label>Destination Street Name</label>
<input type="text" name="streetName"
       maxlength="100" required>

<label>Destination City</label>
<input type="text" name="city"
       maxlength="50" required>

<label>Destination State (2 letters)</label>
<input type="text" name="state"
       maxlength="2"
       pattern="[A-Za-z]{2}" required>

<label>Destination ZIP Code</label>
<input type="text" name="zipCode"
       pattern="[0-9]{5}(-[0-9]{4})?" required>

<label>Moving Date</label>
<input type="date" name="movingDate"
       min="<?php echo date('Y-m-d'); ?>" required>

<label>Total Weight</label>
<input type="number" name="weight"
       min="0.01" max="99999999.99"
       step="0.01" required>

<label>Miles</label>
<input type="number" name="miles"
       min="0" max="999999.99"
       step="0.01" required>

<label>Total Cost</label>
<input type="number" name="cost"
       min="0" max="99999999.99"
       step="0.01" required>

<button type="submit">Book Moving Event</button>

</form>

<?php else: ?>

<h3>Verify Client</h3>

<form method="POST">

<input type="hidden" name="action" value="verify">

<label>Enter Client ID</label>
<input type="text" name="clientID" required>

<button type="submit">Verify Client</button>

</form>

<?php if ($message === "Client does not exist."): ?>

<script>
if (!confirm(
    "Client does not exist. OK to try again, Cancel to create a new account."
)) {
    window.location.href = "create_client.php";
}
</script>

<?php endif; ?>

<?php endif; ?>

</div>
</body>
</html>

<?php mysqli_close($con); ?>
