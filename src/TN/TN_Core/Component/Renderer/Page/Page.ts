import $, {Element} from 'cash-dom';
import LiveReloadResource from "./LiveReloadResource";
import {IComponentFactory} from '../../IComponentFactory';
export default class Page {

    static componentFactory: IComponentFactory;

    static setComponentFactory(factory: IComponentFactory): void {
        Page.componentFactory = factory;
    }

    constructor() {
        $('.tnc-component').each((i: number, element: Element) => {
            Page.componentFactory.createComponent($(element));
        });
        $('link').each((i: number, element: Element) => {
            if ($(element).data('live-reload-resource')) {
                new LiveReloadResource($(element));
            }
        });

        this.observe();
    }

    observe(): void {

    }

    updated(): void {

    }
}