<div class="{$classAttribute}" id="{$idAttribute}">
    <form id="form_edit_email_template" class="mx-auto w-100">
        <input type="hidden" name="key" value="{$emailTemplate->key}">

        <div class="mt-2">
            Available variables for this email:
            <ul class="ms-5">
                {foreach $sampleData as $data}
                    <li>{literal}{{/literal}${$data@key}{literal}}{/literal}</li>
                {/foreach}
            </ul>
        </div>

        <div class="email-subject-width mx-auto">
            <label for="email_subject_field" class="form-label">Email Subject</label>
            <input class="w-100 form-control" type="text" id="email_subject_field" name="subject"
                   value="{$emailTemplate->subject}">
        </div>

        {include file="TN_Core/Model/Email/Header.tpl"}
        <div class="pt-2">
            <div id="email_editor_wrapper" class="mx-auto">
                <textarea id="editor" name="body" class="form-control">{$content}</textarea>
            </div>
            <div id="email_template_loading" style="text-align:center;display:none;">
                <div class="spinner-border" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>

            <div class="d-flex justify-content-center py-2">
                <input type="submit" id="email_template_save_form" class="btn btn-primary"
                       value="Save Template">
            </div>
        </div>
        {include file="TN_Core/Model/Email/Footer.tpl"}

    </form>
</div>