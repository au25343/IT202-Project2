
<?php
session_start();

if (!isset($_SESSION['repID'])) {
    header("Location: index.html");
    exit;
}

require_once dirname(__DIR__, 2) . "/private_db/db_config.php";

$conn = mysqli_connect(
    $db_host,
    $db_user,
    $db_password,
    $db_name,
    3306
);

if (!$conn) {
    die("Database connection failed.");
}

mysqli_set_charset($conn, "utf8mb4");

$message = "";
$newClientID = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["firstName"] ?? "");
    $lastName = trim($_POST["lastName"] ?? "");
    $streetNumber = trim($_POST["streetNumber"] ?? "");
    $streetName = trim($_POST["streetName"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $state = strtoupper(trim($_POST["state"] ?? ""));
    $zipCode = trim($_POST["zipCode"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if (
        $firstName === "" || $lastName === "" ||
        $streetNumber === "" || $streetName === "" ||
        $city === "" || $state === "" ||
        $zipCode === "" || $phone === "" ||
        strlen($firstName) > 50 ||
        strlen($lastName) > 50 ||
        strlen($streetNumber) > 10 ||
        strlen($streetName) > 100 ||
        strlen($city) > 50 ||
        !preg_match('/^[A-Z]{2}$/', $state) ||
        !preg_match('/^\d{5}(-\d{4})?$/', $zipCode) ||
        !preg_match('/^\d{3}-\d{3}-\d{4}$/', $phone)
    ) {
        $message = "Please enter valid information.";
    } else {

        try {
            mysqli_begin_transaction($conn);

            // Generate an unused Client ID
            do {
                $newClientID = (string)random_int(100000, 999999);

                $check = mysqli_prepare(
                    $conn,
                    "SELECT ClientID FROM Client WHERE ClientID = ?"
                );

                mysqli_stmt_bind_param($check, "s", $newClientID);
                mysqli_stmt_execute($check);
                mysqli_stmt_store_result($check);

                $exists = mysqli_stmt_num_rows($check) > 0;
                mysqli_stmt_close($check);

            } while ($exists);

            // Insert into Client
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO Client (ClientID, FirstName, LastName)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $newClientID,
                $firstName,
                $lastName
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Insert into ClientPersonalInfo
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO ClientPersonalInfo
                 (ClientID, StreetNumber, StreetName,
                  City, State, ZipCode, PhoneNumber)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssssss",
                $newClientID,
                $streetNumber,
                $streetName,
                $city,
                $state,
                $zipCode,
                $phone
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            $message = "Customer account created successfully!";

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $newClientID = "";
            $message = "Unable to create customer account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Customer Account</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">

<h1>Moving On Up</h1>
<h2>Create New Customer Account</h2>

<p>Representative ID:
<?php echo htmlspecialchars($_SESSION['repID']); ?>
</p>

<?php if ($message !== ""): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if ($newClientID !== ""): ?>
    <h3>New Client ID:
    <?php echo htmlspecialchars($newClientID); ?>
    </h3>
<?php endif; ?>

<form method="POST">

<label>First Name</label>
<input type="text" name="firstName" maxlength="50" required>

<label>Last Name</label>
<input type="text" name="lastName" maxlength="50" required>

<label>Street Number</label>
<input type="text" name="streetNumber" maxlength="10" required>

<label>Street Name</label>
<input type="text" name="streetName" maxlength="100" required>

<label>City</label>
<input type="text" name="city" maxlength="50" required>

<label>State</label>
<input type="text" name="state" maxlength="2" required>

<label>ZIP Code</label>
<input type="text" name="zipCode"
       pattern="[0-9]{5}(-[0-9]{4})?" required>

<label>Phone Number (123-456-7890)</label>
<input type="tel" name="phone"
       pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}"
       maxlength="12" required>

<button type="submit">Create Customer Account</button>

</form>

<p><a href="book_move.php">Book Moving Event</a></p>
<p><a href="index.html">Back to Login</a></p>

</div>

</body>
</html>

<?php mysqli_close($conn); ?>
