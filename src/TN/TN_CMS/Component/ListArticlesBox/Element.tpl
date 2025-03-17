    {if $orientation === 'hbox'}
        <div class="row vertical-article-cards">
            {foreach $articles as $article}
                {if $article@iteration <= $numArticles}
                    <div class="col-md-4">
                        <div class="card card-ps content-item article-item">
                            <a class="article-link" href="{$BASE_URL}{$article->url}">
                                <div class="thumb-bg" style="background-image: url({$article->thumbnailSrc});"></div>
                                <div class="content-text">
                                    <h4>{$article->title}</h4>
                                    <span class="content-date">{$article->authorName}, {$article->publishedTs|date_format:"%b %e, %Y"}</span>
                                </div>
                            </a>
                        </div>
                    </div>
                {/if}
                {foreachelse}
                <p>No articles found.</p>
            {/foreach}
        </div>
        {elseif $orientation === 'navhbox'}
            {foreach $articles as $article}
                {if $article@iteration <= $numArticles}
                    <div class="col-sm-4 col-md-3">
                        <div class="card card-ps content-item feature-item">
                            <a class="article-link" href="{$BASE_URL}{$article->url}">
                                <div class="thumb-bg" style="background-image: url({$article->thumbnailSrc});"></div>
                                <div class="content-text">
                                    <h4>{$article->title}</h4>
                                    <span class="content-date">{$article->authorName}, {$article->publishedTs|date_format:"%b %e, %Y"}</span>
                                </div>
                            </a>
                        </div>
                    </div>
                {/if}
            {/foreach}
    {elseif $orientation === 'vbox'}
        <div class="content-col-head articles-head">
            <div class="head-icon"></div>
            <h3>{$articlesHeader} Articles</h3>
        </div>
        {foreach $articles as $article}
            {if $article@iteration <= $numArticles}
                <div class="card card-ps content-item {if $article@iteration === 1 && $firstLarger}feature-item{else}article-item{/if}">
                    <a class="article-link" href="{$BASE_URL}{$article->url}">
                        <div class="thumb-bg"
                             style="background-image: url({$article->thumbnailSrc});"></div>
                        <div class="content-text">
                            <h4>{$article->title}</h4>
                            <span class="content-date">{$article->authorName}, {$article->publishedTs|date_format:"%b %e, %Y"}</span>
                        </div>
                    </a>
                </div>
            {/if}
            {foreachelse}
            <p>No articles found.</p>
        {/foreach}
        <div class="content-more">
            <a class="btn btn-more btn-block" href="{$BASE_URL}{$moreLink}">More {$articlesHeader} Articles</a>
        </div>

    {elseif $orientation === 'vlist'}
        <div class="card card-ps link-card">
            {foreach $articles as $article}
                <a href="{$BASE_URL}article/{$article->urlStub}">{$article->title}</a>
            {/foreach}
        </div>
    {/if}
