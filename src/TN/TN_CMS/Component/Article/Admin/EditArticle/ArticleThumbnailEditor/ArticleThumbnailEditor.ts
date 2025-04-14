import $, { Cash } from 'cash-dom';
import HTMLComponent, { ReloadData } from '@tn/TN_Core/Component/HTMLComponent';
import { Carousel } from 'bootstrap';

export default class ArticleThumbnailEditor extends HTMLComponent {
    private carousel: Carousel;
    private $selectedImg: string;
    private imgSrcs: string = '';
    private articleId: string;

    protected observe(): void {
        let $carousel = document.querySelector('.thumbnail-carousel');
        if ($carousel) {
            this.carousel = new Carousel($carousel, { interval: false });
            $carousel.addEventListener('slide.bs.carousel', this.onCarouselChange.bind(this));
        }
        this.$element.on('imgSrcsUpdate', this.onImgSrcsUpdate.bind(this));
        this.articleId = this.$element.data('article-id');
    }

    protected onImgSrcsUpdate(e: any, imgsrcs: string): void {
        this.imgSrcs = imgsrcs;
        this.reload();
    }

    protected getReloadData(): ReloadData {
        let data = super.getReloadData();
        data.imgSrcs = this.imgSrcs;
        data.articleId = $('.tn-tn_cms-component-article-admin-editarticle-editarticle').data('articleid');
        return data;
    }

    protected onCarouselChange(e: any): void {
        let $currentSlide = $(e.relatedTarget);
        this.$selectedImg = $currentSlide.find('img').attr('src');
        //  send the url of the selected image to the article editor using onEdit
        this.$element.trigger('change', {
            thumbnailSrc: this.$selectedImg,
        });
    }

    protected setReloading(reloading: Boolean): void {
        this.$element.find('.component-loading').height(this.$element.height());
        super.setReloading(reloading);
    }
}
