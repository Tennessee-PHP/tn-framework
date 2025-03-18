import {Cash} from 'cash-dom';
import HTMLComponent from './HTMLComponent';

export interface IComponentFactory {
    createComponent(element: Cash): null|HTMLComponent;
}
