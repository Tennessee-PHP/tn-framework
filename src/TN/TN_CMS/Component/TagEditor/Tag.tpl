<div class="tag">
    <span class="text">{$taggedContent->tag->text}</span>
    <a class="remove-primary {if !$taggedContent->primary}d-none {/if} text-primary" href="#"><i class="bi bi-bookmark-plus-fill"></i></a>
    <a class="add-primary {if $taggedContent->primary}d-none {/if} text-primary" href="#"><i class="bi bi-bookmark-plus"></i></a>
    <a class="remove-tag text-danger" href="#"><i class="bi bi-bookmark-x-fill"></i></a>
</div>