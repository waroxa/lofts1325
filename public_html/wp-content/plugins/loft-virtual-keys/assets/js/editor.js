( function( blocks, element, i18n ) {
    const { __ } = i18n;
    const { createElement: el } = element;

    blocks.registerBlockType( 'loft/virtual-keys', {
        title: __( 'Virtual Keys Manager', 'loft-virtual-keys' ),
        icon: 'admin-network',
        category: 'widgets',
        description: __( 'Display the Loft 1325 Virtual Keys interface for administrators.', 'loft-virtual-keys' ),
        supports: {
            html: false,
        },
        edit: function() {
            return el(
                'p',
                { className: 'loft-vk-placeholder' },
                __( 'Virtual keys will be available on the published page for logged-in administrators.', 'loft-virtual-keys' )
            );
        },
        save: function() {
            return null;
        },
    } );
} )( window.wp.blocks, window.wp.element, window.wp.i18n );
