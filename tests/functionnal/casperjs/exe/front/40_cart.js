casper.test.comment('== Cart ==');

casper.test.begin('Cart', 5, function suite(test) {

    var productUrl = '';

    casper.start(thelia2_base_url, function() {

        productUrl = this.getElementAttribute('a.product-info', 'href');

        this.echo("product : " + productUrl);

        this.thenOpen(productUrl, function() {
            this.echo(this.getTitle());
        });

    });

    casper.wait(thelia_default_timeout, function(){
        test.assertExists("#pse-submit", "Add to cart button exists");
        this.capture(screenshot_dir + 'front/40_product.png');
        this.echo("Submit add to cart");
        this.click("#pse-submit");
    });

    casper.wait(thelia_default_timeout, function() {
        //this.echo(this.getHTML());
        test.assertSelectorHasText('.bootbox h3.text-center', 'The product has been added to your cart');
        this.captureSelector(screenshot_dir + 'front/40_added-to-cart.png', '.bootbox');
    });

    casper.wait(thelia_default_timeout, function() {

        this.thenOpen(thelia2_base_url + "cart", function() {
            this.echo(this.getTitle());
            //this.echo(this.getHTML());
            test.assertExists("#cart .table-cart", "Cart table exists");
            test.assertElementCount("#cart .table-cart tbody tr", 2, "Cart contains 1 product")
            var link = this.getElementInfo('#cart .table-cart tbody tr h3.name a');
            test.assertTruthy( link.attributes.href == productUrl, "This is the right product in cart");
            this.capture(screenshot_dir + 'front/40_cart.png');
        });

    });

    casper.run(function() {
        test.done();
    });

});