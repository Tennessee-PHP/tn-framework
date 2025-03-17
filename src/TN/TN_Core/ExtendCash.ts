import $ from 'cash-dom';
import {Cash} from 'cash-dom';
import _ from "lodash";
import {ReloadData} from "./Component/HTMLComponent";

declare module 'cash-dom' {
    interface Cash {
        getFormData(): ReloadData;
    }
}

$.fn.getFormData = function (this: Cash): ReloadData {
    const data: ReloadData = {};
    _.each(this.find('input, select, textarea'), (item) => {
        let $item = $(item);
        if ($item.attr('type') === 'checkbox') {
            data[$item.attr('name')] = $item.is(':checked') ? 1 : 0;
            return;
        }
        if ($item.attr('type') === 'radio') {
            if ($item.is(':checked')) {
                data[$item.attr('name')] = $item.val();
            }
            return;
        }
        data[$item.attr('name')] = $item.val();
    });
    return data;
}
