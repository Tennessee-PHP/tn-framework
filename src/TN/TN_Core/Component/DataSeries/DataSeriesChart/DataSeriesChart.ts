import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class DataSeriesChart extends HTMLComponent {
    private $chart: any;
    observe() {
        // @ts-ignore
        this.$chart = new Chart(this.$element.find('canvas').get(), JSON.parse(this.$element.find('code').html()));
    }
}