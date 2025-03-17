import HTMLComponent from "../../HTMLComponent";

export default class DateInput extends HTMLComponent {

    observe() {
        this.$el.change(this.onChange.bind(this));
    }

    onChange() {
        this.$el.trigger('component:change', this.$el.val());
    }

    getData() {
        let data = {};
        data[this.$el.attr('name')] = this.$el.val();
        return data;
    }
}