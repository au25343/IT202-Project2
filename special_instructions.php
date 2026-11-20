
<?php
session_start();

if (!isset($_SESSION["repID"])) {
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

$repID = $_SESSION["repID"];
$movingID = $_POST["movingID"] ?? $_GET["movingID"] ?? "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $instructions = trim($_POST["instructions"] ?? "");

    if (!ctype_digit((string)$movingID) || $instructions === "") {
        $message = "Please enter valid information.";
    } else {
        // Verify that this move belongs to the logged-in representative.
        $stmt = mysqli_prepare(
            $conn,
            "SELECT MovingID FROM Move WHERE MovingID = ? AND RepID = ?"
        );

        mysqli_stmt_bind_param($stmt, "is", $movingID, $repID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if (!$exists) {
            $message = "Moving event not found.";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO SpecialInstructions (MovingID, Instructions)
                 VALUES (?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "is",
                $movingID,
                $instructions
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Special instructions saved successfully!";
            } else {
                $message = "Unable to save instructions.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Special Instructions</title>
</head>
<body>

<h1>Moving On Up</h1>
<h2>Special Instructions</h2>

<p>Representative ID:
    <?php echo htmlspecialchars((string)$repID); ?>
</p>

<p><?php echo htmlspecialchars($message); ?></p>

<form method="POST">
    <label>Moving ID:</label>
    <input
        type="number"
        name="movingID"
        value="<?php echo htmlspecialchars((string)$movingID); ?>"
        required
    >

    <br><br>

    <label>Special Instructions:</label>
    <br>
    <textarea
        name="instructions"
        rows="5"
        cols="45"
        required
    ></textarea>

    <br><br>

    <button type="submit">Save Instructions</button>
</form>

<p><a href="book_move.php">Back to Booking</a></p>

</body>
</html>

<?php mysqli_close($conn); ?>
