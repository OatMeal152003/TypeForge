<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../api/v1/src/TextService.php';

$mode   = strtolower(trim($_GET['mode'] ?? 'words'));
$allowed_modes = ['words', 'time', 'quote', 'code'];
if (!in_array($mode, $allowed_modes, true)) {
    $mode = 'words';
}

// Curated DB rotation for quote/code (falls back to built-ins below).
if (in_array($mode, ['quote', 'code'], true)) {
    $difficulty = strtolower(trim($_GET['difficulty'] ?? 'intermediate'));
    if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'intermediate';
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getConnection();
        $curated = V1_TextService::getFromDb($db, $mode, $difficulty);
        if ($curated !== null) {
            echo json_encode([
                'success' => true,
                'text'    => $curated['text'],
                'mode'    => $curated['mode'],
                'count'   => isset($_GET['count']) ? max(5, min((int)$_GET['count'], 100)) : 25,
                'text_id' => $curated['text_id'],
                'difficulty' => $curated['difficulty'],
                'source'  => 'db',
            ]);
            exit();
        }
    } catch (Throwable $e) {
        // Fall through to built-in pools.
    }
}
$count  = isset($_GET['count']) ? max(5, min((int)$_GET['count'], 100)) : 25;

$common_words = [
    "the", "be", "of", "and", "a", "to", "in", "he", "have", "it", "that", "for", "they", "i", "with",
    "as", "not", "on", "she", "at", "by", "this", "we", "you", "do", "but", "from", "or", "which", "one",
    "would", "all", "will", "there", "say", "who", "make", "when", "can", "more", "if", "no", "man", "out",
    "other", "so", "what", "time", "up", "go", "about", "than", "into", "could", "state", "only", "new",
    "year", "some", "take", "come", "these", "know", "see", "use", "get", "like", "then", "first", "any",
    "work", "now", "may", "such", "give", "over", "think", "most", "even", "find", "day", "also", "after",
    "way", "many", "must", "look", "before", "great", "back", "through", "long", "where", "much", "should",
    "well", "people", "down", "own", "just", "because", "good", "each", "those", "feel", "seem", "how",
    "high", "too", "place", "little", "world", "very", "still", "nation", "hand", "old", "life", "tell",
    "write", "become", "here", "show", "house", "both", "between", "need", "mean", "call", "develop", "under",
    "last", "right", "move", "thing", "general", "school", "never", "same", "another", "begin", "while",
    "system", "program", "code", "function", "variable", "database", "server", "client", "network", "speed"
];

$code_snippets = [
    "function calculateWpm(chars, seconds) { return Math.round((chars / 5) / (seconds / 60)); }",
    "const db = new PDO('mysql:host=localhost;dbname=typeforge', 'root', 'secret');",
    "SELECT u.username, MAX(t.wpm) FROM users u JOIN typing_sessions t ON u.id = t.user_id GROUP BY u.id;",
    "document.addEventListener('keydown', (e) => { if (e.key === 'Tab') resetTest(); });",
    "export async function fetchMetrics(endpoint) { const res = await fetch(endpoint); return res.json(); }",
    "if (!isset(\$_SESSION['user_id'])) { header('Location: login.php'); exit(); }"
];

$quotes = [
    "Talk is cheap. Show me the code. - Linus Torvalds",
    "Programs must be written for people to read, and only incidentally for machines to execute. - Harold Abelson",
    "Any fool can write code that a computer can understand. Good programmers write code that humans can understand. - Martin Fowler",
    "First, solve the problem. Then, write the code. - John Johnson",
    "Simplicity is prerequisite for reliability. - Edsger W. Dijkstra",
    "Make it work, make it right, make it fast. - Kent Beck"
];

$punctuation = !empty($_GET['punctuation']) && $_GET['punctuation'] !== 'false';
$numbers     = !empty($_GET['numbers']) && $_GET['numbers'] !== 'false';
$difficulty  = strtolower(trim($_GET['difficulty'] ?? 'intermediate'));
if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'intermediate';

// Vocabulary scales with difficulty so every mode honors the level pills.
$beginner_words = [
    "cat", "dog", "run", "sun", "fun", "day", "red", "box", "map", "cup",
    "pen", "bus", "egg", "fox", "hat", "ice", "jam", "kite", "log", "moon",
    "nest", "owl", "pig", "quiz", "rain", "star", "tree", "up", "van", "web",
    "yes", "zoo", "apple", "ball", "cake", "dance", "eager", "fish", "grape", "happy",
    "jump", "kind", "lemon", "mango", "nice", "orange", "play", "quick", "river", "smile",
];
$advanced_words = [
    "algorithm", "asynchronous", "authentication", "bandwidth", "benchmark", "boolean", "buffer",
    "cache", "compiler", "concurrency", "configuration", "cryptography", "database", "debugger",
    "deployment", "encapsulation", "framework", "function", "gateway", "hexadecimal", "index",
    "infrastructure", "interface", "javascript", "kernel", "latency", "library", "middleware",
    "network", "optimization", "packet", "polymorphism", "protocol", "query", "queue",
    "recursion", "refactor", "repository", "runtime", "scalar", "schema", "script", "server",
    "socket", "stack", "syntax", "thread", "token", "variable", "vector", "widget",
];
$vocab = $common_words;
if ($difficulty === 'beginner') $vocab = $beginner_words;
elseif ($difficulty === 'advanced') $vocab = $advanced_words;

if ($mode === 'code') {
    $selected_text = $code_snippets[array_rand($code_snippets)];
} elseif ($mode === 'quote') {
    $selected_text = $quotes[array_rand($quotes)];
} else {
     
     
    $word_count = ($mode === 'time') ? max(200, (int)$count * 5) : $count;
    $word_count = min($word_count, 500);
    $words_pool = [];

    $punctuations_list = ['.', ',', '?', '!', ';', ':', '-', '"'];

    for ($i = 0; $i < $word_count; $i++) {
         
        if ($numbers && rand(1, 6) === 1) {
            $words_pool[] = (string)rand(1, 9999);
            continue;
        }

        $word = $vocab[array_rand($vocab)];

         
        if ($punctuation) {
            if ($i === 0 || rand(1, 5) === 1) {
                $word = ucfirst($word);
            }
            if (rand(1, 4) === 1) {
                $punct = $punctuations_list[array_rand($punctuations_list)];
                if ($punct === '"') {
                    $word = '"' . $word . '"';
                } else {
                    $word = $word . $punct;
                }
            }
        }

        $words_pool[] = $word;
    }

    $selected_text = implode(' ', $words_pool);
}

echo json_encode([
    'success' => true,
    'text'    => $selected_text,
    'mode'    => $mode,
    'count'   => $count
]);
exit();