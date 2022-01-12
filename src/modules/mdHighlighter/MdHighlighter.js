#lx:module lx.MdHighlighter;

#lx:use lx.CssColorSchema;

class MdHighlighter extends lx.Module #lx:namespace lx {
    static initCssAsset(css) {
        css.addClass('md-container', {
            padding: '20px'
        });
        css.addClass('md-paragraph', {
            margin: 0,
            // marginTop: '10px',
            // marginBottom: '10px',
            paddingTop: '10px',
            paddingBottom: '10px'
        });
        css.addClass('md-codeblock', {
            marginTop: '20px',
            marginBottom: '20px',
            padding: '20px',
            backgroundColor: lx.CssColorSchema.bodyBackgroundColor
        });
        css.addClass('md-blockquote', {
            margin: 0,
            marginTop: '20px',
            marginBottom: '20px',
            paddingLeft: '20px',
            backgroundColor: lx.CssColorSchema.bodyBackgroundColor,
            borderLeft: 'solid 2px' + lx.CssColorSchema.coldSoftColor
        });
        css.addClass('md-table', {
            marginTop: '20px',
            marginBottom: '20px',
            border: 'solid 1px ' + lx.CssColorSchema.widgetBorderColor,
            borderCollapse: 'collapse'
        });
        css.addClass('md-table-header', {
            padding: '10px',
            border: 'solid 1px ' + lx.CssColorSchema.widgetBorderColor,
        });
        css.inheritClass('md-table-cell', 'md-table-header');

    }

    static highlight(tag) {
        console.log('lx.MdHighlighter');
        console.log(tag.getAttribute('code-type'));
        console.log(tag.innerHTML);
    }
}
