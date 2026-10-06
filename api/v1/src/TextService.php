<?php
// TypeForge REST v1 — TextService.
// Extracted from api/get-text.php. No DB required. Pure function for testability.

class V1_TextService {
    // Curated rotation: quote/code modes prefer active DB rows (admin-curated
    // via admin/texts.php), filtered by difficulty. Returns null when DB has
    // nothing usable (caller falls back to built-ins).
    public static function getFromDb(PDO $db, string $mode, string $difficulty = 'intermediate'): array|null {
        if (!in_array($mode, ['quote', 'code'], true)) return null;
        if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'intermediate';
        $stmt = $db->prepare("SELECT id, title, content, difficulty FROM typing_texts WHERE category = :mode AND difficulty = :diff AND is_active = 1 ORDER BY RAND() LIMIT 1");
        $stmt->execute([':mode' => $mode, ':diff' => $difficulty]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || trim((string)($row['content'] ?? '')) === '') {
            // No curated text at this difficulty — try any difficulty before giving up.
            $stmt = $db->prepare("SELECT id, title, content, difficulty FROM typing_texts WHERE category = :mode AND is_active = 1 ORDER BY RAND() LIMIT 1");
            $stmt->execute([':mode' => $mode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$row || trim((string)($row['content'] ?? '')) === '') return null;
        return ['text' => $row['content'], 'mode' => $mode, 'text_id' => (int)$row['id'], 'title' => $row['title'], 'difficulty' => $row['difficulty'], 'source' => 'db'];
    }

    public static function get(string $mode, int $count, bool $punctuation, bool $numbers, string $difficulty = 'intermediate'): array {
        $allowed = ['words', 'time', 'quote', 'code'];
        if (!in_array($mode, $allowed, true)) $mode = 'words';
        $count = max(5, min($count, 100));
        if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'intermediate';

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

        if ($mode === 'code') {
            $text = $code_snippets[random_int(0, count($code_snippets) - 1)];
        } elseif ($mode === 'quote') {
            $text = $quotes[random_int(0, count($quotes) - 1)];
        } else {
            // Vocabulary scales with difficulty so every mode honors the level pills.
            $vocab = $common_words;
            if ($difficulty === 'beginner') $vocab = $beginner_words;
            elseif ($difficulty === 'advanced') $vocab = $advanced_words;
            $word_count = ($mode === 'time') ? max(200, $count * 5) : $count;
            $word_count = min($word_count, 500);
            $pool = [];
            $puncts = ['.', ',', '?', '!', ';', ':', '-', '"'];
            for ($i = 0; $i < $word_count; $i++) {
                if ($numbers && random_int(1, 6) === 1) {
                    $pool[] = (string)random_int(1, 9999);
                    continue;
                }
                $word = $vocab[random_int(0, count($vocab) - 1)];
                if ($punctuation) {
                    if ($i === 0 || random_int(1, 5) === 1) $word = ucfirst($word);
                    if (random_int(1, 4) === 1) {
                        $p = $puncts[random_int(0, count($puncts) - 1)];
                        $word = ($p === '"') ? '"' . $word . '"' : $word . $p;
                    }
                }
                $pool[] = $word;
            }
            $text = implode(' ', $pool);
        }

        return ['text' => $text, 'mode' => $mode, 'count' => $count, 'difficulty' => $difficulty, 'source' => 'builtin'];
    }
}
