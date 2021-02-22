{extends file='frontend/index/index.tpl'}

{block name="frontend_index_header_css_screen_stylesheet" prepend}
    <link href="{link file='frontend/_public/src/css/card.css'}" media="all" rel="stylesheet" type="text/css" />
{/block}

{block name='frontend_index_content_left'}{/block}

{* Content top container *}
{block name="frontend_index_content_top"}
    <nav class="content--breadcrumb block">
        <ul class="breadcrumb--list">
            <li role="menuitem" class="breadcrumb--entry is--active">
                <h4><img class="left emerchantpay-icon" alt="emerchantpay Icon" src="{link file="frontend/_public/src/img/icon.png"}"> {$method}</h4>
            </li>
        </ul>
    </nav>
{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div class="content listing--content">
        <div class="emerchantpay-container">
            <div class="emerchantpay-card-box">
                <div><img src="{link file="frontend/_public/src/img/emerchantpay.png"}" alt="emerchantpay Logo"></div>
                <div class="card-wrapper"></div>
                <form id="emerchantpay_form" action="{$action}" method="post">
                    <div>
                        <input class="input--field" placeholder="•••• •••• •••• ••••" type="text"
                                name="cc_number" autocomplete="off">
                    </div>
                    <div>
                        <input class="input--field" placeholder="Full name" type="text" name="cc_full_name">
                    </div>
                    <div>
                        <input class="input--field" placeholder="MM/YY" type="text"
                                name="cc_expiry" autocomplete="off">
                    </div>
                    <div>
                        <input class="input--field" placeholder="***" type="password"
                                name="cc_cvv" autocomplete="off">
                    </div>
                    {foreach from=$params key=name item=value}
                        <input type="hidden" name="{$name}" value="{$value}" />
                    {/foreach}
                    <input class="btn is--primary" type="submit" value="{$button}" />
                </form>
            </div>
        </div>
    </div>
    <script src="{link file="frontend/_public/src/js/card.min.js"}" type="text/javascript"></script>
    <script>
        new Card({
            form: document.getElementById('emerchantpay_form'),
            container: '.card-wrapper',
            formSelectors: {
                nameInput: 'input[name="cc_full_name"]',
                numberInput: 'input[name="cc_number"]',
                expiryInput: 'input[name="cc_expiry"]',
                cvcInput: 'input[name="cc_cvv"]'
            },
            messages: {
                legalText: '&copy;{$smarty.now|date_format: '%Y'} emerchantpay LTD'
            },
            width: (window.innerWidth > 400) ? 300 : 180,
            debug: false,
            // Default values for rendered fields - options*}
            values: {
                number: '•••• •••• •••• ••••',
                name: 'Full Name',
                expiry: '••/••',
                cvc: '***'
            },
        });

        document.getElementById('emerchantpay_form').onsubmit = function(){
            var submit_btn = document.getElementById('emerchantpay_form').elements["submit"];
            submit_btn.setAttribute("disabled", "disabled");
        };
    </script>
{/block}
