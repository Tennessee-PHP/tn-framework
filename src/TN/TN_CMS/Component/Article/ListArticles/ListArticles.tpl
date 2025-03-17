<div class="{$classAttribute}" id="{$idAttribute}" data-reload-url="{path route=$reloadRoute}">
    {include file="TN_Core/Component/Loading/Loading.tpl" 
        title="Loading Articles" 
        message="Loading Articles..."
    }

    <div class="{if !empty($tag)}d-none{/if} row mb-3">
        <div class="col col-12 col-md-6 col-lg-3">
            {$userSelect->render()}
        </div>

        <div class="col col-12 col-md-6 col-lg-3">
            {$categorySelect->render()}
        </div>
    </div>

    <div class="articles-row row mb-3 gy-3">
        {foreach $articles as $article}
            <div class="col-6 col-sm-4 col-md-4 vertical-article-card">
                <div class="card card-ps content-item article-item">
                    <a class="article-link text-body text-decoration-none"
                       href="{$BASE_URL}article/{$article->urlStub}">
                        <div class="thumb-bg"
                             style="background-image: url({$article->thumbnailSrc});"></div>
                        <div class="content-text">
                            <h5>{$article->title}</h5>
                            <span class="content-date">{$article->authorName}, {$article->publishedTs|date_format:"%b %e, %Y"}</span>
                        </div>
                    </a>
                </div>
            </div>
            {foreachelse}
            <p>No articles found.</p>
        {/foreach}
    </div>

    {$pagination->render()}
</div>