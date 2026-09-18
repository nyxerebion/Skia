<?php
$api = file_get_contents(__DIR__ . '/../api/like-comment.php');
if (strpos($api, "normalizeID(\$input['id'] ?? '')") === false) {
    fwrite(STDERR, "Comment like API is not normalizing hashed IDs.\n");
    exit(1);
}

$postsPage = file_get_contents(__DIR__ . '/../posts/index.php');
if (strpos($postsPage, "const targetPostId =") === false) {
    fwrite(STDERR, "Posts page scroll script is missing the target-id logic.\n");
    exit(1);
}

echo "Like/hash regression checks passed\n";
