<?php
/**
 * Časté dotazy. Hlavní klíčové slovo: „dakboard alternative“ (otázka o DAKboardu).
 * Otázky a odpovědi jsou v DayBoard\Site\Faq – stejná data se vypisují i jako
 * strukturovaná data FAQPage v hlavičce stránky.
 *
 * @var array<string,string> $page
 */

use DayBoard\Site\Faq;
?>
<section class="page-hero">
    <div class="container">
        <h1><?= e($page['h1']) ?></h1>
        <p class="lead">Odpovědi na to, na co se lidé u rodinné nástěnky DayBoard ptají nejčastěji: co potřebuje ke spuštění, jak funguje dětský účet, jak dostat kalendář na tablet na zdi a jestli je DayBoard vhodná alternativa k DAKboardu (DAKboard alternative).</p>
    </div>
</section>

<section class="section section--flush">
    <div class="container prose-layout">
        <div class="prose faq">
            <?php foreach (Faq::items() as $index => $item): ?>
                <section class="faq__item" aria-labelledby="otazka-<?= $index + 1 ?>">
                    <h2 id="otazka-<?= $index + 1 ?>"><?= e($item['question']) ?></h2>
                    <?= $item['answer'] ?>
                </section>
            <?php endforeach; ?>
        </div>

        <aside class="aside-card" aria-labelledby="aside-title">
            <h2 id="aside-title">Nenašli jste odpověď?</h2>
            <p>Zkuste podrobný návod nebo přehled funkcí.</p>
            <p><a class="btn btn--block" href="<?= e(url('/jak-to-funguje')) ?>">Jak DayBoard nainstalovat</a></p>
            <p><a class="btn btn--block" href="<?= e(url('/funkce')) ?>">Widgety rodinné nástěnky</a></p>
        </aside>
    </div>
</section>
