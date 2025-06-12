<?php
session_start();

// ====== CONFIG / "Database" ======
$questionData = [
    [
        "text" => "What is your favorite programming language?",
        "options" => ["C++", "Python", "Java", "Other"]
    ],
    [
        "text" => "How many years of coding experience do you have?",
        "options" => ["<1", "1-3", "3-5", "5+"]
    ],
    [
        "text" => "What do you enjoy most about programming?",
        "options" => []
    ],
    [
        "text" => "Do you prefer frontend or backend development?",
        "options" => ["Frontend", "Backend", "Full Stack", "Not Sure"]
    ],
    [
        "text" => "What is one feature you wish more programming languages had?",
        "options" => []
    ]
];

$responseFile = 'responses.json';

// ====== FUNCTIONS ======
function loadResponses($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveResponse($file, $username, $answers) {
    $responses = loadResponses($file);
    $responses[] = ["user" => $username, "answers" => $answers];
    file_put_contents($file, json_encode($responses, JSON_PRETTY_PRINT));
}

function deleteResponses($file) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
}

// ====== HANDLING USER ENTRY ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $name = trim($_POST['username']);
    if (strtolower($name) === 'admin') {
        $_SESSION['user'] = 'admin';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (preg_match('/^[a-zA-Z0-9_]{3,16}$/', $name)) {
        $_SESSION['user'] = $name;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Invalid username. Use 3–16 letters, digits, or underscores.";
    }
}

// ====== LOGIC ======
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];

    // ==== ADMIN VIEW ====
    if ($user === 'admin') {
        if (isset($_POST['delete_all'])) {
            deleteResponses($responseFile);
            $message = "All responses deleted.";
        }
        $responses = loadResponses($responseFile);
        ?>
        <h2>Admin Panel</h2>
        <form method="post">
            <button name="delete_all">Delete All Responses</button>
        </form>
        <?php if (!empty($message)) echo "<p style='color:green'>$message</p>"; ?>
        <pre><?php print_r($responses); ?></pre>
        <form method="post" action="?logout=1"><button>Logout</button></form>
        <?php exit();
    }

    // ==== SURVEY FORM ====
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
        saveResponse($responseFile, $user, $_POST['answers']);
        echo "<p>Thanks for completing the survey, $user!</p>";
        session_destroy();
        exit();
    }

    echo "<h2>Welcome, $user! Please complete the survey:</h2>";
    echo '<form method="post">';
    foreach ($questionData as $i => $q) {
        echo "<p>" . htmlspecialchars($q['text']) . "</p>";
        if (!empty($q['options'])) {
            foreach ($q['options'] as $opt) {
                echo "<label><input type='radio' name='answers[$i]' value='$opt' required> $opt</label><br>";
            }
        } else {
            echo "<input type='text' name='answers[$i]' required><br>";
        }
        echo "<br><br>";
    }
    echo '<button type="submit">Submit Survey</button>';
    echo '</form>';
    exit();
}

// ====== LOGIN PAGE ======
?>
<h2>Enter your username (type "admin" for admin access)</h2>
<form method="post">
    <input type="text" name="username" required>
    <button type="submit">Start</button>
</form>
<?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>

<?php
// ====== LOGOUT HANDLER ======
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
