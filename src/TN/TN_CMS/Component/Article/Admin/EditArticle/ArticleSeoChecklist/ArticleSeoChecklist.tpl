<div class="card mb-3 {$classAttribute}" id="{$idAttribute}">

    <div class="card-body">
        <h3>SEO Checklist</h3>
        <form>
            <div class="form-group">
                <label for="main_keyword" class="form-label">Main SEO Keyword</label>
                <input type="text" id="main_keyword" name="main_keyword" class="form-control" value="{$article->primarySeoKeyword}"
                       aria-describedby="seo_help">
                <small id="audience_help" class="form-text text-muted">SEO Description</small>
                <div class="is-invalid d-none">
                </div>
            </div>
            <div>
                <ul id="seo_checklist" class="list-group-flush px-0">
                    <li id="keyword_density" class="list-group-item px-0 text-muted"><span id="keyword_density_status"><i class="bi bi-three-dots"></i></span> Keyword density - Keyword at least one time per 100 words</li>
                    <li id="keyword_header_density" class="list-group-item px-0 text-muted"><span id="keyword_header_density_status"><i class="bi bi-three-dots"></i></span> Keyword header density - Keyword in at least one header/subheader, and at least one time per five headers/subheaders</li>
                    <li id="keyword_start_title" class="list-group-item px-0 text-muted"><span id="keyword_start_title_status"><i class="bi bi-three-dots"></i></span> Keyword at start of title</li>
                    <li id="keyword_start_meta" class="list-group-item px-0 text-muted"><span id="keyword_start_meta_status"><i class="bi bi-three-dots"></i></span> Keyword at start of meta description</li>
                    <li id="keyword_in_url" class="list-group-item px-0 text-muted"><span id="keyword_in_url_status"><i class="bi bi-three-dots"></i></span> Keyword in URL</li>
                    <li id="inbound_link" class="list-group-item px-0 text-muted"><span id="inbound_link_status"><i class="bi bi-three-dots"></i></span> At least one inbound link (domain.com)</li>
                    <li id="outbound_link" class="list-group-item px-0 text-muted"><span id="outbound_link_status"><i class="bi bi-three-dots"></i></span> At least one outbound link (besides domain.com)</li>
                    <li id="keyword_in_alt_tag" class="list-group-item px-0 pb-0 text-muted"><span id="keyword_in_alt_tag_status"><i class="bi bi-three-dots"></i></span> At least one image with keyword as alt tag </li>
                </ul>
            </div>
        </form>
    </div>
</div>