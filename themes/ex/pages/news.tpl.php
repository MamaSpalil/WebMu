<?php if (!defined("insite")) die("no access"); ?>
<main class="page">
    <header class="page-header">
        <img class="icon-glyph" src="assets/icons/scroll.svg" alt="">
        <h1 class="page-title"><?= h(lang("news.title", "News")) ?></h1>
        <p class="page-subtitle"><?= h(lang("news.subtitle", "Announcements")) ?></p>
        <div class="divider" aria-hidden="true"></div>
    </header>

    <section class="news-list">
        <?php if (!empty($no_table)): ?>
            <p class="note warn"><?= h(lang("admin.news.no_table", "Table webmu_news is missing.")) ?></p>
        <?php elseif (!$news): ?>
            <p class="text-mute"><?= h(lang("news.empty", "No news posts yet.")) ?></p>
        <?php else: ?>
            <?php foreach ($news as $n):
                $title  = trim((string)($n["title"]  ?? ""));
                $body   = trim((string)($n["body"]   ?? ""));
                $author = trim((string)($n["author"] ?? ""));
                $ts     = (string)($n["posted_at"] ?? "");
                $ts_iso = $ts !== "" ? date("Y-m-d\TH:i", strtotime($ts) ?: time()) : "";
                $ts_h   = $ts !== "" ? date("d.m.Y H:i", strtotime($ts) ?: time()) : "";
            ?>
                <article class="news-post panel panel-corner">
                    <header class="news-post-head">
                        <h2 class="news-post-title"><?= h($title) ?></h2>
                        <div class="news-post-meta">
                            <span class="news-post-author"><?= h(lang("news.posted_by", "Posted by")) ?>
                                <strong><?= h($author !== "" ? $author : lang("news.author", "Administrator")) ?></strong></span>
                            <time class="news-post-date" datetime="<?= h($ts_iso) ?>"><?= h($ts_h) ?></time>
                        </div>
                    </header>
                    <div class="news-post-body"><?= nl2br(h($body)) ?></div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (($total_pages ?? 1) > 1): ?>
        <?= pager_html($page ?? 1, $total_pages, "index.php?m=news&page=%d") ?>
    <?php endif; ?>
</main>
