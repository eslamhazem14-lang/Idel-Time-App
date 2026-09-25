<?php

namespace Database\Seeders;

/**
 * Obviously-fake but realistic demo task definitions.
 * Each entry: [category slug, type, title, description, instructions, minutes, reward, difficulty, skills, payload]
 */
final class DemoContent
{
    public static function categories(): array
    {
        return [
            ['AI Evaluation', 'ai-evaluation', 'sparkles', 'Rate and critique AI model outputs.'],
            ['Code Review', 'code-review', 'code', 'Review small code snippets and diffs.'],
            ['QA Testing', 'qa-testing', 'check-circle', 'Structured test cases and checks.'],
            ['Website Testing', 'website-testing', 'globe', 'Walk through real web flows and report issues.'],
            ['Documentation', 'documentation', 'book', 'Verify technical documentation.'],
            ['Data Validation', 'data-validation', 'database', 'Check records and labels against sources.'],
            ['Technical Research', 'technical-research', 'search', 'Short technical lookups and answers.'],
            ['Security Review', 'security-review', 'shield', 'Spot security issues in code and configs.'],
            ['UI Review', 'ui-review', 'layout', 'Give feedback on interface details.'],
            ['Bug Reproduction', 'bug-reproduction', 'bug', 'Reproduce reported bugs in a clean environment.'],
        ];
    }

    public static function tasks(): array
    {
        return [
            ['ai-evaluation', 'ai_evaluation', 'Rate an AI answer about SQL LEFT JOIN', 'Evaluate a model explanation of LEFT JOIN semantics.', "Read the prompt and the AI response.\nRate correctness, relevance and overall quality from 1 to 5.\nIn comments, point out any factual errors.", 3, '0.35', 'easy', ['sql'],
                ['prompt' => 'What is the difference between INNER JOIN and LEFT JOIN in SQL?', 'response' => "An INNER JOIN returns only rows with matches in both tables. A LEFT JOIN returns all rows from the left table and the matching rows from the right table; when there is no match, the right side columns are NULL.\n\nExample: SELECT u.name, o.id FROM users u LEFT JOIN orders o ON o.user_id = u.id;", 'safety_applicable' => false]],
            ['ai-evaluation', 'ai_evaluation', 'Evaluate AI advice on storing passwords', 'Check whether a model gives safe password storage advice.', "Rate the response. Pay close attention to security correctness.\nFlag anything that would be unsafe in production.", 4, '0.45', 'medium', ['security'],
                ['prompt' => 'How should I store user passwords in my Node.js app?', 'response' => 'Use a slow, salted hashing algorithm designed for passwords such as bcrypt, scrypt or Argon2id. Never store plain text or use fast hashes like MD5/SHA-1. Example with bcrypt: const hash = await bcrypt.hash(password, 12); and verify with bcrypt.compare(). Also enforce rate limiting on login.', 'safety_applicable' => true]],
            ['ai-evaluation', 'ai_evaluation', 'Rate an AI regex explanation', 'Is the model\'s explanation of this regex correct?', "Check the explanation against the regex.\nScore each criterion and explain mistakes in comments.", 3, '0.30', 'easy', ['regex'],
                ['prompt' => 'Explain the regex ^\\d{3}-\\d{4}$', 'response' => 'It matches a string that is exactly three digits, a hyphen, and four digits, like 555-1234. The ^ and $ anchor the match to the whole string.', 'safety_applicable' => false]],
            ['ai-evaluation', 'ai_evaluation', 'Evaluate an AI git rebase explanation', 'Score a model answer about interactive rebase.', "Rate correctness, relevance and quality.\nNote any commands that are wrong or dangerous.", 4, '0.40', 'medium', ['git'],
                ['prompt' => 'How do I squash my last 3 commits?', 'response' => "Run git rebase -i HEAD~3, change 'pick' to 'squash' (or 's') for the second and third commits, save, then edit the combined message. If you already pushed, you'll need git push --force-with-lease.", 'safety_applicable' => false]],
            ['code-review', 'code_review', 'Review a PHP discount calculation', 'Find bugs in a small pricing function.', "Read the function.\nAnswer each question, and list any issues with line references.", 5, '0.50', 'medium', ['php'],
                ['language' => 'php', 'code' => "function applyDiscount(float \$price, int \$percent): float\n{\n    if (\$percent > 100) {\n        \$percent = 100;\n    }\n    return \$price - \$price * \$percent / 100;\n}", 'questions' => ['What happens with a negative percent?', 'Is float appropriate for money here? Why or why not?']]],
            ['code-review', 'code_review', 'Review a JavaScript debounce helper', 'Spot issues in a debounce implementation.', "Check correctness of the debounce.\nConsider `this` binding and argument forwarding.", 5, '0.55', 'medium', ['javascript'],
                ['language' => 'javascript', 'code' => "function debounce(fn, wait) {\n  let t;\n  return function () {\n    clearTimeout(t);\n    t = setTimeout(fn, wait);\n  };\n}", 'questions' => ['Are arguments forwarded to fn?', 'Is `this` preserved?']]],
            ['code-review', 'code_review', 'Review a Python file reader', 'Resource handling review of a short Python function.', 'Review for resource leaks and error handling.', 4, '0.45', 'easy', ['python'],
                ['language' => 'python', 'code' => "def read_config(path):\n    f = open(path)\n    data = f.read()\n    return json.loads(data)", 'questions' => ['Is the file always closed?', 'What happens if the JSON is invalid?']]],
            ['code-review', 'code_review', 'Review a Go HTTP handler', 'Check error handling in a Go handler.', 'Look for unchecked errors and status code issues.', 6, '0.70', 'hard', ['go'],
                ['language' => 'go', 'code' => "func handler(w http.ResponseWriter, r *http.Request) {\n    body, _ := io.ReadAll(r.Body)\n    var req Payload\n    json.Unmarshal(body, &req)\n    w.Write([]byte(\"ok\"))\n}", 'questions' => ['Which errors are ignored?', 'What status code should invalid JSON return?']]],
            ['security-review', 'code_review', 'Spot the SQL injection', 'Security review of a query builder snippet.', 'Identify security issues and suggest a fix.', 4, '0.60', 'medium', ['security', 'php'],
                ['language' => 'php', 'code' => "\$sql = \"SELECT * FROM users WHERE email = '\" . \$_GET['email'] . \"'\";\n\$rows = \$pdo->query(\$sql)->fetchAll();", 'questions' => ['Describe the vulnerability.', 'Show a safe version.']]],
            ['security-review', 'code_review', 'Review a JWT verification snippet', 'Check a token verification function for weaknesses.', 'Focus on algorithm confusion and expiry checks.', 7, '0.90', 'hard', ['security', 'javascript'],
                ['language' => 'javascript', 'code' => "const payload = jwt.decode(token);\nif (payload.role === 'admin') {\n  grantAdmin();\n}", 'questions' => ['Is the signature verified?', 'What should be used instead of decode?']]],
            ['website-testing', 'website_qa', 'Test the demo signup form on mobile', 'Walk through a fake signup form on a phone-sized viewport.', "Use a mobile viewport (or a real phone).\nFollow the steps and report anything broken or confusing.", 7, '0.75', 'easy', ['qa'],
                ['url' => 'https://example.com/', 'test_steps' => "1. Open the URL on a mobile viewport (375px wide).\n2. Check that all text is readable without zooming.\n3. Tap every link and note what happens.\n4. Report layout issues with screenshots.", 'devices' => 'Any phone or DevTools device emulation']],
            ['website-testing', 'website_qa', 'Check keyboard navigation on a landing page', 'Accessibility spot-check with keyboard only.', "Navigate using only Tab / Shift+Tab / Enter.\nReport focus visibility and any traps.", 6, '0.65', 'medium', ['accessibility'],
                ['url' => 'https://example.org/', 'test_steps' => "1. Load the page.\n2. Press Tab repeatedly and note whether focus is always visible.\n3. Try to activate every link with Enter.\n4. Note any element you cannot reach.", 'devices' => 'Desktop browser']],
            ['website-testing', 'website_qa', 'Verify page title and meta tags', 'Quick SEO sanity check of a page.', 'Inspect the page source and report title, description and Open Graph tags.', 4, '0.40', 'easy', ['seo'],
                ['url' => 'https://example.net/', 'test_steps' => "1. Open the URL.\n2. View source.\n3. Report <title>, meta description and og:* tags (or their absence).", 'devices' => null]],
            ['qa-testing', 'multiple_choice', 'Which HTTP status fits this case?', 'Pick the correct status code for an API scenario.', 'Choose the most appropriate status code.', 2, '0.20', 'easy', ['http'],
                ['question' => 'A client sends a valid request, but the resource was already modified by someone else (optimistic lock conflict). Which status code fits best?', 'options' => ['400 Bad Request', '404 Not Found', '409 Conflict', '500 Internal Server Error'], 'multiple' => false]],
            ['qa-testing', 'multiple_choice', 'Select all idempotent HTTP methods', 'Classic HTTP semantics check.', 'Select every method that is idempotent per RFC 9110.', 2, '0.20', 'easy', ['http'],
                ['question' => 'Which of these HTTP methods are idempotent?', 'options' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], 'multiple' => true]],
            ['qa-testing', 'text_response', 'Write test cases for a password rule', 'List boundary test cases for a validation rule.', 'Write at least 5 test inputs with expected results.', 5, '0.50', 'medium', ['testing'],
                ['question' => 'A password must be 10–64 characters and include at least one digit. List boundary test cases with the expected pass/fail result for each.']],
            ['documentation', 'documentation_verification', 'Verify claims about PHP array_map', 'Check three statements against the PHP manual.', 'Open the documentation and mark each claim.', 4, '0.40', 'easy', ['php'],
                ['source_url' => 'https://www.php.net/manual/en/function.array-map.php', 'excerpt' => null, 'claims' => ['array_map preserves string keys when exactly one array is passed.', 'The callback can be null.', 'array_map modifies the input array in place.']]],
            ['documentation', 'documentation_verification', 'Check a README excerpt for accuracy', 'Verify install instructions in a fake README.', 'Compare the claims to the excerpt; mark anything that contradicts it.', 3, '0.35', 'easy', ['docs'],
                ['source_url' => null, 'excerpt' => "## Installation\nRequires Node 18 or newer.\nRun `npm install acme-widgets`.\nThe package ships ESM only; CommonJS require() is not supported.", 'claims' => ['The package supports Node 16.', 'You can import it with require().', 'Installation uses npm install acme-widgets.']]],
            ['documentation', 'documentation_verification', 'Verify MDN claims about fetch()', 'Check statements about the Fetch API.', 'Use MDN to verify each claim.', 5, '0.50', 'medium', ['javascript'],
                ['source_url' => 'https://developer.mozilla.org/en-US/docs/Web/API/fetch', 'excerpt' => null, 'claims' => ['fetch() rejects the promise on HTTP 404.', 'fetch() sends cookies to same-origin URLs by default.', 'The response body can be read only once.']]],
            ['data-validation', 'multiple_choice', 'Is this email address syntactically valid?', 'Quick data validation item.', 'Decide whether the address is valid per common RFC 5322 practice.', 2, '0.15', 'easy', ['data'],
                ['question' => 'Is "jane.doe+test@example.test" a syntactically valid email address?', 'options' => ['Valid', 'Invalid', 'Unsure'], 'multiple' => false]],
            ['data-validation', 'text_response', 'Normalize five fake company names', 'Clean up inconsistent company names.', 'Return one normalized name per line, in the same order.', 3, '0.30', 'easy', ['data'],
                ['question' => "Normalize these names (trim, title case, remove legal suffix):\n  acme widgets, inc.\nGLOBEX CORPORATION\ninitech llc\n umbrella  corp \nHooli Ltd."]],
            ['technical-research', 'text_response', 'Find the default port for PostgreSQL', 'A quick factual lookup with a source.', 'Answer and include a link to an authoritative source.', 2, '0.20', 'easy', ['databases'],
                ['question' => 'What is the default TCP port for PostgreSQL? Cite an official source.']],
            ['technical-research', 'text_response', 'Compare two JS date libraries', 'Short comparison for a decision.', 'In 3–5 bullet points, compare date-fns and Day.js for bundle size and API style.', 8, '0.90', 'medium', ['javascript'],
                ['question' => 'Compare date-fns and Day.js: bundle size, tree-shaking, immutability, and API style. Keep it under 150 words.']],
            ['ui-review', 'text_response', 'Critique a button label set', 'UX copy review for a confirmation dialog.', 'Suggest clearer labels and explain briefly.', 3, '0.30', 'easy', ['ux'],
                ['question' => 'A delete dialog says "Are you sure?" with buttons "OK" and "Cancel". Propose better copy for the title and both buttons, and explain why in 2 sentences.']],
            ['ui-review', 'multiple_choice', 'Pick the accessible color pair', 'Contrast check between color pairs.', 'Select every pair that passes WCAG AA for normal text (4.5:1).', 3, '0.30', 'medium', ['accessibility'],
                ['question' => 'Which text/background pairs meet WCAG AA for normal text?', 'options' => ['#767676 on #FFFFFF', '#999999 on #FFFFFF', '#FFFFFF on #0B0D10', '#7C5CFC on #0B0D10'], 'multiple' => true]],
            ['bug-reproduction', 'bug_reproduction', 'Reproduce: npm script fails on Windows paths', 'Check whether a script breaks with backslashes.', 'Follow the steps in a clean folder and report the outcome.', 8, '0.90', 'medium', ['node'],
                ['environment' => 'Node 20+, any OS (Windows preferred)', 'steps' => "mkdir repro && cd repro\nnpm init -y\nnpm pkg set scripts.clean=\"rm -rf dist/*\"\nnpm run clean", 'expected' => 'The script runs without error on all platforms.', 'actual' => "Reported: fails on Windows with \"'rm' is not recognized\"."]],
            ['bug-reproduction', 'bug_reproduction', 'Reproduce: Python float rounding surprise', 'Confirm a reported rounding behavior.', 'Run the snippet and report exactly what you see.', 3, '0.30', 'easy', ['python'],
                ['environment' => 'Python 3.10+', 'steps' => 'python3 -c "print(round(2.675, 2))"', 'expected' => 'Reporter expected 2.68', 'actual' => 'Reported: prints 2.67']],
            ['bug-reproduction', 'bug_reproduction', 'Reproduce: CSS sticky header not sticking', 'Check a sticky positioning report.', 'Create the HTML file, open it, scroll, and report.', 6, '0.60', 'medium', ['css'],
                ['environment' => 'Any modern browser', 'steps' => "Create index.html with:\n<div style=\"overflow:hidden\"><header style=\"position:sticky;top:0\">Header</header><div style=\"height:3000px\"></div></div>\nOpen it and scroll.", 'expected' => 'Header stays at the top while scrolling.', 'actual' => 'Reported: header scrolls away.']],
        ];
    }

    public static function answerFor(string $type, array $payload, int $seed): array
    {
        return match ($type) {
            'ai_evaluation' => ['correctness' => 3 + $seed % 3, 'relevance' => 4 + $seed % 2, 'quality' => 3 + $seed % 3] + (($payload['safety_applicable'] ?? false) ? ['safety' => 5] : []) + ['comments' => ['Accurate and concise; example query is correct.', 'Mostly right, but it could mention that unmatched right-side columns are NULL-filled in more detail.', 'Clear explanation. No factual issues found after checking the docs.'][$seed % 3]." (review {$seed})"],
            'code_review' => ['verdict' => 'request_changes', 'issues' => ['Negative input is not handled; float is used for money which causes rounding errors.', 'Arguments and this are not forwarded; use fn.apply(this, args) inside the timeout.', 'Errors are silently ignored and the response code is always 200.'][$seed % 3]." (review {$seed})", 'answers' => array_map(fn ($q) => 'Checked: '.mb_strtolower(rtrim($q, '?')).' — see issues above (#'.$seed.').', $payload['questions'] ?? [])],
            'website_qa' => ['result' => ['pass', 'partial'][$seed % 2], 'environment' => ['Chrome 128 / macOS 14', 'Firefox 130 / Ubuntu 24.04', 'Safari / iOS 18'][$seed % 3], 'findings' => "Walked through all steps (run {$seed}). Page loads fast; the single link works. Minor: body text is small on 375px width."],
            'multiple_choice' => ['choices' => ($payload['multiple'] ?? false) ? [0, 2] : [min(2, count($payload['options']) - 1)]],
            'documentation_verification' => ['verdicts' => array_map(fn ($i) => ['accurate', 'inaccurate', 'accurate'][$i % 3], array_keys($payload['claims'])), 'notes' => "Claim 2 contradicts the documentation; verified against the source on this run ({$seed})."],
            'bug_reproduction' => ['reproduced' => ['yes', 'no', 'partially'][$seed % 3], 'environment' => ['Windows 11, Node 20.11', 'macOS 14, Node 22', 'Ubuntu 24.04, Python 3.12'][$seed % 3], 'notes' => "Followed the steps exactly in a clean directory (attempt {$seed}); output matched the report."],
            default => ['text' => "Answer #{$seed}: ".['PostgreSQL listens on 5432 by default (postgresql.org/docs/current/runtime-config-connection.html).', 'Acme Widgets, Globex, Initech, Umbrella, Hooli.', 'Title: "Delete project?" Buttons: "Delete project" and "Keep project" — explicit verbs reduce mistakes.'][$seed % 3]],
        };
    }
}
