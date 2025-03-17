import Page from "./Page";
import {pageReference} from '@ts/componentMap';

let page: Page|null = null;
export default function PageSingleton(): Page {
    if (page === null) {
        page = new pageReference();
    }
    return page;
}