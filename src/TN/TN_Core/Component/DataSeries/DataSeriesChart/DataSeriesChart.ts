import HTMLComponent from '@tn/TN_Core/Component/HTMLComponent';

export default class DataSeriesChart extends HTMLComponent {
    private $chart: any;
    observe() {
        const canvas = this.$element.find('canvas').get(0);
        const configStr = this.$element.find('code').html() ?? '';
        if (!canvas || !configStr.trim()) {
            return;
        }
        const config = JSON.parse(configStr);
        // @ts-ignore
        this.$chart = new Chart(canvas, config);
    }
}