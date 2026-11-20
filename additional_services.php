
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

mysqli_set_charset($conn, "utf8mb4");

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$repID = $_SESSION["repID"];
$movingID = trim($_POST["movingID"] ?? "");
$instructions = "";
$message = "";
$found = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if (!ctype_digit($movingID)) {
        $message = "Please enter a valid Moving ID.";
    } else {

        // Check that this moving event belongs to the representative
        $sql = "SELECT MovingID FROM Move
                WHERE MovingID = ? AND RepID = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $movingID, $repID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $found = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if (!$found) {
            $message = "Moving event not found.";
        } else {

            if ($action === "update") {

                $instructions = trim(
                    $_POST["instructions"] ?? ""
                );

                if ($instructions === "") {
                    $message = "Please enter special instructions.";
                } else {

                    // Update existing instructions
                    $sql = "UPDATE SpecialInstructions
                            SET Instructions = ?
                            WHERE MovingID = ?";

                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param(
                        $stmt,
                        "si",
                        $instructions,
                        $movingID
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Add instructions if no record exists yet
                    $stmt = mysqli_prepare(
                        $conn,
                        "SELECT MovingID FROM SpecialInstructions
                         WHERE MovingID = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $movingID);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);

                    $hasInstructions =
                        mysqli_stmt_num_rows($stmt) > 0;

                    mysqli_stmt_close($stmt);

                    if (!$hasInstructions) {
                        $sql = "INSERT INTO SpecialInstructions
                                (MovingID, Instructions)
                                VALUES (?, ?)";

                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param(
                            $stmt,
                            "is",
                            $movingID,
                            $instructions
                        );
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }

                    $message = "Special instructions updated successfully!";
                }
            }

            // Load current instructions
            if ($action === "search") {
                $sql = "SELECT Instructions
                        FROM SpecialInstructions
                        WHERE MovingID = ?";

                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $movingID);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                if ($row = mysqli_fetch_assoc($result)) {
                    $instructions = $row["Instructions"];
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Special Instructions</title>

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
    margin-top: 15px;
}

input, textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px;
    margin-top: 6px;
}

button {
    background: #253858;
    color: white;
    padding: 12px;
    border: none;
    margin-top: 18px;
    cursor: pointer;
}
</style>
</head>

<body>

<nav>
    <a href="index.html">Home</a>
    <a href="book_move.php">Book Move</a>
    <a href="cancel_move.php">Cancel Move</a>
</nav>

<div class="container">

<h1>Moving On Up</h1>
<h2>Update Special Instructions</h2>

<p>Representative ID:
    <?php echo clean($repID); ?>
</p>

<?php if ($message !== ""): ?>
    <p><?php echo clean($message); ?></p>
<?php endif; ?>

<?php if (!$found): ?>

<h3>Find Moving Event</h3>

<form method="POST">
    <input type="hidden" name="action" value="search">

    <label>Moving ID</label>
    <input type="number" name="movingID" min="1" required>

    <button type="submit">Search Moving Event</button>
</form>

<?php else: ?>

<h3>Moving ID: <?php echo clean($movingID); ?></h3>

<form method="POST"
      onsubmit="return confirm('Save these changes?');">

    <input type="hidden" name="action" value="update">
    <input type="hidden" name="movingID"
           value="<?php echo clean($movingID); ?>">

    <label>Special Instructions</label>

    <textarea name="instructions" rows="6" required><?php
        echo clean($instructions);
    ?></textarea>

    <button type="submit">Update Instructions</button>
</form>

<p>
    <a href="additional_services.php">
        Search Another Moving Event
    </a>
</p>

<?php endif; ?>

</div>
</body>
</html>

<?php mysqli_close($conn); ?>
