<div class="card mb-3 {$classAttribute}" id="{$idAttribute}" data-article-id="{$article->id}" data-reload-url="{path route=$reloadRoute}">
    <div class="card-body">
        <h3>Thumbnail</h3>

        {if empty($candidateImgSrcs)}
            <div class="alert-warning d-flex h-100 align-items-center justify-content-center">
                <div class="text-center">
                    Please upload or paste an image into the article
                </div>
            </div>
        {else}
            <div class="thumbnail-carousel carousel slide" id="thumbnail_carousel">
                <div class="carousel-indicators">
                    {foreach $candidateImgSrcs as $key => $imageSrc}
                        <button type="button" data-bs-target="#thumbnail_carousel" data-bs-slide-to="{$key}"
                                class="{if $key == 0}active{/if}" aria-current="{if $key == 0}true{/if}"
                                aria-label="Image {$key + 1}"></button>
                    {/foreach}
                </div>
                <div class="carousel-inner">
                    {foreach $candidateImgSrcs as $key => $imageSrc}
                        <div class="carousel-item {if $imageSrc === $article->thumbnailSrc}active{/if}">
                            <img src="{$imageSrc}" class="d-block w-100" alt="">
                        </div>
                    {/foreach}
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#thumbnail_carousel"
                        data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#thumbnail_carousel"
                        data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        {/if}
    </div>
</div>