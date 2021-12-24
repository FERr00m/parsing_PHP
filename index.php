<?php

// Загружаем phpQuery
require_once("vendor/autoload.php");

function get_contents($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
/**
 * Создает массив новостей по заданным селекторам в нужном количестве
 *
 * @param string $url  Целевая страница для парсинга
 * @param array $selectors  Массив селекторов
 * @param integer $count  Количество новостей
 *
 * @return array  Массив новостей
 */
function make_array_news(string $url, array $selectors, int $count): array
{
    $html = get_contents($url);

    //Получаем DOM
    $dom = phpQuery::newDocument($html);

    $news = array();
    $counter = 0;
    foreach ($dom->find("{$selectors['item']}") as $key => $value) {
        $pq = pq($value);  //pq() это аналог $ в jQuery

        // Собираем массив с нужными данными
        $news[$key]["news-href"] = preg_replace("/^\/?/", "/", $pq->find("{$selectors['news-href']}")->attr("href"));
        $news[$key]["news-title"] = $pq->find("{$selectors['news-title']}")->text();
        $news[$key]["news-date"] = $pq->find("{$selectors['news-date']}")->text();
        $news[$key]["news-img"] = preg_replace("/^\/?/", "/", $pq->find("{$selectors['news-img']}")->attr("src"));

        $counter++;
        if ($counter === $count) break; // Необходимое количество новостей
    }
    // PhpQuery очень удобная библиотека, но, к сожалению, слишком тяжелая.
    // Так что после прохода по элементам рекомендуется выгружать документ:
    phpQuery::unloadDocuments();

    return $news;
}

$url = $_GET['url'];
$domain = parse_url($url)['host'];

switch ($domain) {
    case 'aem-group.ru':
        $selectors = array(
            'item' => '.news-list__item',
            'news-href' => 'a',
            'news-title' => '.news-info-preview__title',
            'news-date' => '.news-info-preview__date',
            'news-img' => 'img'
        );
        break;
    case 'rosatom.ru':
        $selectors = array (
            'item' => '[id^="bx_3"]',
            'news-href' => 'a',
            'news-title' => '.title',
            'news-date' => '.date',
            'news-img' => 'img'
        );
        break;
}

//echo "<pre>";
//var_dump(make_array_news($url, $selectors, 4));
//echo "</pre>";

foreach (make_array_news($url, $selectors, 4) as $item) :?>
    <a href="https://<?=$domain . $item['news-href']?>" class="news-item" target="_blank">
        <img style="max-width: 100%; height: auto" src="https://<?=$domain . $item['news-img'] ?: ''?>" width="238" height="284" alt="">
        <div class="news-descr">
            <div class="news-date"><?=$item['news-date']?></div>
            <div class="news-text">
                <?=$item['news-title']?>
            </div>
        </div>
    </a>
<?endforeach;?>
