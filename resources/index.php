<?php

use DevCoder\DotEnv;

require __DIR__ . '/../vendor/autoload.php';

$args = explode('/', substr($_SERVER['REQUEST_URI'], 1));

if ($args[0] !== 'resources'):
    header("Location: /404.html");
    exit();
endif;

(new DotEnv(__DIR__ . '/../.env'))->load();

$db_host = getenv('DATABASE_HOST');
$db_name = getenv('DATABASE_NAME');
$db_user = getenv('DATABASE_USER');
$db_pass = getenv('DATABASE_PASS');

$domain = getenv('DATABASE_DOMAIN');

$path = get_image_path($db_host, $db_user, $db_pass, $db_name, $domain);

if ($path):
    if (!file_exists($path)):
        mkdir($path, 0777, true);
    endif;
endif;

$latest_post_count = (int)getenv('LATEST_POST_COUNT');

if (!$args[1]):
    render_index($db_host, $db_user, $db_pass, $db_name, $domain, $latest_post_count, $path);
endif;

if ($args[1] === 'images'):
    if ($args[2]):
        if (file_exists($path . $args[2])) {
            header('Content-Type: image/jpg');
            readfile($path . $args[2]);
            exit();
        } else {
            header('Content-Type: image/jpg');
            exit();
        }
    else:
        header('Content-Type: image/jpg');
        exit();
    endif;
endif;

if ($args[1] !== 'blog'):
    header("Location: /404.html");
    exit();
endif;

if (!$args[2]):
    render_all_blogs($db_host, $db_user, $db_pass, $db_name, $domain, $path);
endif;

render_blog($db_host, $db_user, $db_pass, $db_name, $domain, $args[2], $path);

function render_index($db_host, $db_user, $db_pass, $db_name, $domain, $latest_post_count, $path)
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    $sql = "";
    $sql .= "SELECT blog_posts.title, ";
    $sql .= "       blog_posts.slug, ";
    $sql .= "       blog_posts.teaser, ";
    $sql .= "       blog_posts.image, ";
    $sql .= "       blog_posts.publish_timestamp ";
    $sql .= "FROM   `blogs_leads` ";
    $sql .= "       LEFT JOIN `blog_posts` ";
    $sql .= "              ON `blog_posts`.`blog_leads_id` = `blogs_leads`.`id` ";
    $sql .= "WHERE  `blogs_leads`.`domain` = ? AND `blog_posts`.`status_id` = 1 ";
    $sql .= "AND `blog_posts`.`publish_timestamp` <= ? ";
    $sql .= "ORDER  BY `blog_posts`.`publish_timestamp` DESC ";
    $sql .= "LIMIT  ?";

    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $domain, $now, $latest_post_count);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);

    $template = file_get_contents('resources_index.tpl');
    $template_latest = file_get_contents('blog_single_short.tpl');

    $posts_view = '';
    foreach ($posts as $post):
        $posts_view_tmp = $template_latest;
        $posts_view_tmp = str_replace('[[TITLE]]', $post['title'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[DATE]]', date('m/d/Y', strtotime($post['publish_timestamp'])), $posts_view_tmp);
        $posts_view_tmp = str_replace('[[TEASER]]', $post['teaser'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[IMAGE]]', $post['image'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[SLUG]]', $post['slug'], $posts_view_tmp);
        $posts_view .= $posts_view_tmp;
    endforeach;
    $page = str_replace('[[BLOGS]]', $posts_view, $template);
    echo $page;
    exit();

}

function render_blog($db_host, $db_user, $db_pass, $db_name, $domain, $slug, $path)
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    $slug = str_replace(".html", "", $slug);

    $sql = "";
    $sql .= "SELECT blog_posts.title, ";
    $sql .= "       blog_posts.slug, ";
    $sql .= "       blog_posts.tagline, ";
    $sql .= "       blog_posts.teaser, ";
    $sql .= "       blog_posts.image, ";
    $sql .= "       blog_posts.content, ";
    $sql .= "       blog_posts.publish_timestamp ";
    $sql .= "FROM   `blogs_leads` ";
    $sql .= "       LEFT JOIN `blog_posts` ";
    $sql .= "              ON `blog_posts`.`blog_leads_id` = `blogs_leads`.`id` ";
    $sql .= "WHERE  `blogs_leads`.`domain` = ? ";
    $sql .= "  AND  `blog_posts`.`slug` = ?  AND `blog_posts`.`status_id` = 1 ";
    $sql .= "  AND `blog_posts`.`publish_timestamp` <= ? ";
    $sql .= "ORDER  BY `blog_posts`.`publish_timestamp` DESC ";
    $sql .= "LIMIT  1";
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $domain,  $slug, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);

    $template = file_get_contents('article-template.tpl');

    $page = '';

    if (!$posts):
        header("Location: /404.html");
        exit();
    endif;

    foreach ($posts as $post):
        $posts_view_tmp = $template;
        $posts_view_tmp = str_replace('[[TITLE]]', $post['title'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[DATE]]', date('m/d/Y', strtotime($post['publish_timestamp'])), $posts_view_tmp);
        $posts_view_tmp = str_replace('[[TEASER]]', $post['teaser'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[IMAGE]]', $post['image'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[TAGLINE]]', $post['tagline'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[CONTENT]]', $post['content'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[SLUG]]', $post['slug'], $posts_view_tmp);
        $page .= $posts_view_tmp;
    endforeach;
    echo $page;
    exit();

}

function render_all_blogs($db_host, $db_user, $db_pass, $db_name, $domain, $path)
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    $sql = "";
    $sql .= "SELECT blog_posts.title, ";
    $sql .= "       blog_posts.slug, ";
    $sql .= "       blog_posts.teaser, ";
    $sql .= "       blog_posts.image, ";
    $sql .= "       blog_posts.publish_timestamp ";
    $sql .= "FROM   `blogs_leads` ";
    $sql .= "       LEFT JOIN `blog_posts` ";
    $sql .= "              ON `blog_posts`.`blog_leads_id` = `blogs_leads`.`id` ";
    $sql .= "WHERE  `blogs_leads`.`domain` = ? AND `blog_posts`.`id` IS NOT NULL AND `blogs_leads`.`id` IS NOT NULL  AND `blog_posts`.`status_id` = 1 ";
    $sql .= "  AND `blog_posts`.`publish_timestamp` <= ? ";
    $sql .= "ORDER  BY `blog_posts`.`publish_timestamp` DESC ";
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $domain, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);

    $template = file_get_contents('all_posts.tpl');
    $template_latest = file_get_contents('blog_single_short.tpl');

    $posts_view = '';
    foreach ($posts as $post):
        $posts_view_tmp = $template_latest;
        $posts_view_tmp = str_replace('[[TITLE]]', $post['title'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[DATE]]', date('m/d/Y', strtotime($post['publish_timestamp'])), $posts_view_tmp);
        $posts_view_tmp = str_replace('[[TEASER]]', $post['teaser'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[IMAGE]]', $post['image'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[SLUG]]', $post['slug'], $posts_view_tmp);
        $posts_view_tmp = str_replace('[[TAGLINE]]', $post['tagline'], $posts_view_tmp);
        $posts_view .= $posts_view_tmp;
    endforeach;
    $page = str_replace('[[BLOGS]]', $posts_view, $template);
    echo $page;
    exit();
}

function slugify($text, string $divider = '-')
{
    $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, $divider);
    $text = preg_replace('~-+~', $divider, $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

function get_image_path($db_host, $db_user, $db_pass, $db_name, $domain)
{
    $path = getenv('SHARED_IMAGES_PATH');

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    $sql = "SELECT `id`, ";
    $sql .= "       `leads_id` ";
    $sql .= "FROM   `blogs_leads` ";
    $sql .= "WHERE  `blogs_leads`.`domain` = ? AND `blogs_leads`.`status_id` = 1 ";
    $sql .= "LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_row();

    $path .= '/' . $posts[1] . '/' . $posts[0] . '/';

    return $path;
}
