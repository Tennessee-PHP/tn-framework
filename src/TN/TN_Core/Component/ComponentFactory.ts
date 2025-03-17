import $, {Cash} from "cash-dom";
import componentMap from '@ts/componentMap';
import HTMLComponent from "./HTMLComponent";

const ComponentFactory = (element: Cash): null|HTMLComponent => {
    let s: string;
    for (s in componentMap) {
        if ($(element).hasClass(s)) {
            // @ts-ignore
            return new (componentMap[s])(element);
        }
    }
    return null;
}

export default ComponentFactory;