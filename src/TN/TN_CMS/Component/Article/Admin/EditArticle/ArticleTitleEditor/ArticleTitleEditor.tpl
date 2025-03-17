<div class="{$classAttribute}" id="{$idAttribute}">
    <input class="editable-input-title p-0 display-4 w-100" type="text"
           value="{$article->title}" placeholder="Enter your article title..." style="border: 0 !important;"/>

    <textarea
            class="article-summary editable-input-summary w-100 lead border-0"
            type="text" placeholder="Enter your article description...">{$article->description}</textarea>

    <div class="d-flex align-items-center mb-3">
        <a href="{$BASE_URL}articles?authorId={$article->authorId}">
            <img src="https://tennessee.imgix.net/{$article->authorAvatarUrl}?auto=compress,format&fit=facearea&facepad=2.5&w=75&h=75&mask=ellipse"
                 class="rounded-circle h-auto me-3 staff-pic"
                 alt="{$article->authorName}'s {$article->title}">
        </a>
        {if $canEditAuthor}
        <select id="staffer_select" class="p-0 display-6 border-0" name="staffer">
            <option {if !$article->authorId}selected {/if}
                    style="font-size: 1rem;"
                    value=""
                    data-avatarurl="https://tennessee.imgix.net/{$author['avatarUrl']}?auto=compress,format&fit=facearea&facepad=2.5&w=75&h=75&mask=ellipse"
            > Staff</option>
            {foreach $authorOptions as $author}
                <option {if $article->authorId === $author['id']}selected {/if}
                        value="{$author['id']}"
                        data-avatarurl="https://tennessee.imgix.net/{$author['avatarUrl']}?auto=compress,format&fit=facearea&facepad=2.5&w=75&h=75&mask=ellipse"
                > {$author['name']}</option>
            {/foreach}
        </select>
        {else}
        <a class="display-6 link-dark link-underline link-underline-opacity-0"
           href="{$BASE_URL}articles?authorId={$article->authorId}">
            {$article->authorName}</a>
        {/if}

        <input type="datetime-local" class="ms-2 text-secondary form-control border-0" style="max-width: 14rem;" id="publishedTs"
               name="startTs" value="{$article->publishedTs|date_format:"%m/%d/%y %H:%M"}"
               aria-describedby="start_date_help"/>

    </div>
</div>