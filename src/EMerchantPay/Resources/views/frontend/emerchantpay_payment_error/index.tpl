{extends file='frontend/index/index.tpl'}

{block name='frontend_index_content_left'}{/block}

{* Content top container *}
{block name="frontend_index_content_top"}
    <nav class="content--breadcrumb block">
        <ul class="breadcrumb--list">
            <li role="menuitem" class="breadcrumb--entry is--active">
                <h4>{$type}</h4>
                <meta itemprop="position" content="0">
            </li>
        </ul>
    </nav>
{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div class="content listing--content">
        <div class="hero-unit category--teaser panel has--border is--rounded">
            <h1 class="hero--headline panel--title">{$title}</h1>
            <div class="hero--text panel--body is--wide">
                <div class="teaser--text-long">
                    <p>{$message}</p>
                </div>
            </div>
        </div>
        <div class="example-content--actions">
            <a class="btn"
               href="{url controller=checkout action=cart}"
               title="Change Cart">Change Cart
            </a>
            <a class="btn is--primary"
               href="{url controller=checkout action=confirm}"
               title="Checkout">Checkout Payment</a>
        </div>
    </div>
{/block}
