<?PHP

$product = wc_get_product($post->ID);
$price = $product->get_meta('lulu_print_cost_excl_tax');
$errors = $product->get_meta('lulu_errors');

$cover_pdf_id  = $product->get_meta('lulu_cover_pdf_attachment_id');
$coverImage = wp_get_attachment_image_src( $cover_pdf_id );

$interior_pdf_id  = $product->get_meta('lulu_interior_pdf_attachment_id');
$interiorImage = wp_get_attachment_image_src( $interior_pdf_id );

$coverPdfStr = __('Select Cover PDF', PD_LULU_FULFILLMENT_DOMAIN);
$interiorPdfStr = __('Select Interior PDF', PD_LULU_FULFILLMENT_DOMAIN);

if($errors) {
?>
    <div style='color:red;'><strong>Errors:</strong>
        <?PHP foreach($errors as $key => $value) {?>
            <div style='padding-left: 5px;'><?php echo($key); ?>: <?php echo(is_array($value) ? implode('|', $value) : $value); ?></div>
        <?PHP }?>
    </div>
<?PHP
}
?>

<div>
    <p><strong>Print Cost:</strong></p>
    <div>
        <?=$price ? wc_price($price) : __('Undetermined', PD_LULU_FULFILLMENT_DOMAIN)?>
    </div>
</div>

<div>
    <p><strong>Cover PDF</strong></p>
    <div>
        <a href="#" class="pd-lulu-upl" title="<?=$coverPdfStr?>" data-uploader-title="<?=$coverPdfStr?>" style="display: block;">
            <?= $coverImage ? ("<img src='" . $coverImage[0] . "' />") : $coverPdfStr ?>
        </a>
        <a href="#" class="pd-lulu-rmv" style="display:<?=$coverImage ? 'block' : 'none'?>;">Remove PDF</a>
        <input type="hidden" name="lulu_cover_pdf_attachment_id" value="<?=$cover_pdf_id?>">
    </div>
</div>

<div>
    <p><strong>Interior PDF</strong></p>
    <div>
        <a href="#" class="pd-lulu-upl" title="<?=$interiorPdfStr?>" data-uploader-title="<?=$interiorPdfStr?>" style="display: block;">
            <?= $interiorImage ? ("<img src='" . $interiorImage[0] . "' />") : $interiorPdfStr ?>
        </a>
        <a href="#" class="pd-lulu-rmv" style="display:<?=$interiorImage ? 'block' : 'none'?>">Remove PDF</a>
        <input type="hidden" name="lulu_interior_pdf_attachment_id" value="<?=$interior_pdf_id?>">
    </div>
</div>

<script>
      jQuery(function($){

        // on upload button click
        $('body').on( 'click', '.pd-lulu-upl', function(e){

            e.preventDefault();

            var button = $(this),
            custom_uploader = wp.media({
                title: button.data('uploader-title'),
                library : {
                    uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                    type : 'application/pdf'
                },
                button: {
                    text: '<?=__('Select this document', PD_LULU_FULFILLMENT_DOMAIN)?>' // button label text
                },
                multiple: false
            }).on('select', function() { // it also has "open" and "close" events
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                button.html('<img src="' + attachment.sizes.thumbnail.url + '">');
                button.next().show().next().val(attachment.id);
            }).open();

        });

        // on remove button click
        $('body').on('click', '.pd-lulu-rmv', function(e){

            e.preventDefault();

            var button = $(this);
            button.next().val(''); // emptying the hidden field
            var uplTxt = button.prev().data('uploader-title');
            button.hide().prev().html(uplTxt);
        });
    });
</script>