<?php
/**
 * Block Editor functionality for TYPE III AUDIO
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue the editor script and editor CSS
 */
add_action('enqueue_block_editor_assets', function () {
    // The main block-editor script, embedded inline so you only need this single file.
    $script = <<<JS
( function() {
    const { registerPlugin } = wp.plugins;
    const { BlockControls, InspectorControls } = wp.blockEditor;
    const { ToolbarButton, Slot, Fill, TextControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { getBlockAttributes, getSelectedBlockClientIds } = wp.data.select('core/block-editor');
    const { updateBlockAttributes } = wp.data.dispatch('core/block-editor');
    const { addFilter } = wp.hooks;
    const { createElement, Fragment } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { RichText } = wp.blockEditor;
    const { createHigherOrderComponent } = wp.compose;


    // Register a new Audio Note block type
    registerBlockType('t3a/audio-note', {
        title: 'Audio Note',
        description: 'A block for content that will only be visible in the editor and used for audio narration notes.',
        icon: 'playlist-audio',
        category: 'common',
        
        attributes: {
            content: {
                type: 'string',
                source: 'html',
                selector: 'p',
                default: '',
            },
            // This block will always have audioNote set to true
            audioNote: {
                type: 'boolean',
                default: true,
            }
        },
        
        // Define how the block appears in the editor
        edit: function(props) {
            const { attributes, setAttributes, className } = props;
            
            return createElement(
                'div',
                { 
                    className: className,
                    'data-audio-note': 'true'
                },
                createElement(
                    RichText,
                    {
                        tagName: 'p',
                        value: attributes.content,
                        onChange: function(content) {
                            setAttributes({ content: content });
                        },
                        placeholder: 'Enter your audio note here...'
                    }
                )
            );
        },
        
        // Define how the block is saved
        save: function(props) {
            const { attributes } = props;
            
            return createElement(
                RichText.Content,
                {
                    tagName: 'p',
                    value: attributes.content,
                }
            );
        }
    });

    // Register a new Type 3 Player block type
    registerBlockType('t3a/player', {
        title: 'TYPE III AUDIO Player',
        description: 'A block that inserts the TYPE III AUDIO player.',
        icon: 'controls-volumeon',
        category: 'common',
        
        attributes: {
            attributes: {
                type: 'string',
                default: ''
            }
        },
        
        // Define how the block appears in the editor
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        'div',
                        { className: 't3a-player-inspector' },
                        createElement(
                            TextControl,
                            {
                                label: 'Player Attributes',
                                help: 'Enter attributes like: mp3-url="example.mp3" sticky="true"',
                                value: attributes.attributes,
                                onChange: (value) => setAttributes({ attributes: value })
                            }
                        )
                    )
                ),
                createElement(
                    'div',
                    { 
                        className: 't3a-player-block',
                        'data-t3a-player': 'true'
                    },
                    createElement(
                        'div',
                        {
                            style: {
                                padding: '0px',
                                height: '50px',
                                background: '#f0f0f0',
                                borderRadius: '4px',
                                border: '1px dashed #ccc'
                            }
                        },
                        createElement(
                            'audio',
                            {
                                controls: true,
                                style: {
                                    width: '100%',
                                    opacity: '0.5'
                                }
                            }
                        )
                    )
                )
            );
        },
        
        // Define how the block is saved
        save: function(props) {
            const { attributes } = props;
            const shortcode = '[type_3_player' + (attributes.attributes ? ' ' + attributes.attributes : '') + ']';
            
            return createElement(
                'div',
                null,
                shortcode
            );
        }
    });
    
    // Function to check if the block type is allowed to have our buttons
    const isAllowedBlockType = (blockName) => {
        const allowedBlockTypes = [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/quote',
            'core/code',
            'core/details',
            'core/preformatted',
            'core/pullquote',
            'core/table',
            'core/verse',
            'core/freeform', // Classic block
            'core/media-text',
            'core/group'
        ];
        
        return allowedBlockTypes.includes(blockName);
    };

    // Function to check if block type can have must narrate button
    const isMustNarrateAllowedBlockType = (blockName) => {
        const allowedBlockTypes = [
            'core/image',
            'core/details'
        ];
        return allowedBlockTypes.includes(blockName);
    };

    // Create a higher-order component that adds our custom toolbar
    const withT3AToolbar = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            const { attributes, setAttributes, clientId, name } = props;
            const isDoNotNarrateActive = attributes?.doNotNarrate || false;
            const isMustNarrateActive = attributes?.mustNarrate || false;
            
            const toggleDoNotNarrate = () => {
                const currentValue = attributes?.doNotNarrate || false;
                setAttributes({
                    doNotNarrate: !currentValue
                });
            };

            const toggleMustNarrate = () => {
                const currentValue = attributes?.mustNarrate || false;
                setAttributes({
                    mustNarrate: !currentValue
                });
            };
            
            return createElement(
                Fragment,
                null,
                createElement(
                    BlockEdit,
                    props
                ),
                createElement(
                    BlockControls,
                    { group: 'block' },
                    // Only show do not narrate button for allowed block types
                    isAllowedBlockType(name) && createElement(
                        ToolbarButton,
                        {
                            icon: 'microphone',
                            title: 'Do not narrate',
                            onClick: toggleDoNotNarrate,
                            isActive: isDoNotNarrateActive
                        }
                    ),
                    // Only show must narrate button for allowed block types
                    isMustNarrateAllowedBlockType(name) && createElement(
                        ToolbarButton,
                        {
                            icon: 'megaphone',
                            title: 'Must narrate',
                            onClick: toggleMustNarrate,
                            isActive: isMustNarrateActive
                        }
                    )
                )
            );
        };
    }, 'withT3AToolbar');
    
    // Add the toolbar to the block editor
    addFilter(
        'editor.BlockEdit',
        't3a/toolbar',
        withT3AToolbar
    );
    
    // Register support for both attributes for all blocks
    addFilter(
        'blocks.registerBlockType',
        't3a/attributes',
        ( settings, name ) => {
            settings.attributes = {
                ...settings.attributes,
                doNotNarrate: {
                    type: 'boolean',
                    default: false,
                },
                mustNarrate: {
                    type: 'boolean',
                    default: false,
                }
            };
            return settings;
        }
    );

    // Add data attributes to block wrapper in editor
    addFilter(
        'blocks.getBlockDefaultClassName',
        't3a/add-data-attributes',
        ( className, blockName, attributes ) => {
            return className;
        }
    );

    // Add data attributes to block wrapper props in editor
    addFilter(
        'blocks.getSaveContent.extraProps',
        't3a/add-data-attributes',
        ( props, blockType, attributes ) => {
            if ( attributes?.doNotNarrate ) {
                props['data-do-not-narrate'] = 'true';
            }
            if ( attributes?.mustNarrate ) {
                props['data-must-narrate'] = 'true';
            }
            return props;
        }
    );

    // Add data attributes to block wrapper props in editor
    addFilter(
        'editor.BlockListBlock',
        't3a/with-data-attributes',
        ( BlockListBlock ) => {
            return ( props ) => {
                const { attributes } = props;
                const wrapperProps = props.wrapperProps || {};
                
                if ( attributes?.doNotNarrate ) {
                    wrapperProps['data-do-not-narrate'] = 'true';
                }
                if ( attributes?.mustNarrate ) {
                    wrapperProps['data-must-narrate'] = 'true';
                }
                
                return createElement( BlockListBlock, { ...props, wrapperProps } );
            };
        }
    );

} )();
JS;

    // Register and enqueue an empty JS file, then inject the above script as inline code.
    wp_register_script(
        't3a-do-not-narrate-editor',
        '', // We'll add the JS inline.
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-hooks', 'wp-plugins', 'wp-compose'),
        '1.0',
        true
    );
    wp_enqueue_script('t3a-do-not-narrate-editor');
    wp_add_inline_script('t3a-do-not-narrate-editor', $script);

    // Add editor‐only CSS with more specific selectors
    $editor_css = <<<CSS
.wp-block[data-do-not-narrate="true"],
.block-editor-block-list__block[data-do-not-narrate="true"],
div[data-do-not-narrate="true"] {
    position: relative !important;
    outline: none !important;
    padding: 8px !important;
    background-color: rgba(221, 0, 0, 0.03) !important;
    border-radius: 4px !important;
}

.wp-block[data-do-not-narrate="true"]::before,
.block-editor-block-list__block[data-do-not-narrate="true"]::before,
div[data-do-not-narrate="true"]::before {
    content: "🚫 Do not narrate" !important;
    display: block !important;
    position: absolute !important;
    top: -20px !important;
    right: 0 !important;
    background-color: #d00 !important;
    color: white !important;
    padding: 2px 8px !important;
    font-size: 11px !important;
    border-radius: 3px !important;
    z-index: 99999 !important;
}

.wp-block[data-do-not-narrate="true"]::after,
.block-editor-block-list__block[data-do-not-narrate="true"]::after,
div[data-do-not-narrate="true"]::after {
    content: "" !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    left: 0 !important;
    border: 2px dashed #d00 !important;
    border-radius: 4px !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

/* Audio Note Block Styles */
div[data-type="t3a/audio-note"] {
    position: relative !important;
    outline: none !important;
    padding: 8px !important;
    background-color: rgba(0, 128, 255, 0.03) !important;
    border-radius: 4px !important;
}

/* Remove top margin from first and last paragraphs in audio notes */
div[data-type="t3a/audio-note"] p:first-child {
    margin-top: 0 !important;
}

div[data-type="t3a/audio-note"] p:last-child {
    margin-bottom: 0 !important;
}

div[data-type="t3a/audio-note"]::before {
    content: "📝 Audio note" !important;
    display: block !important;
    position: absolute !important;
    top: -20px !important;
    right: 0 !important;
    background-color: #0080ff !important;
    color: white !important;
    padding: 2px 8px !important;
    font-size: 11px !important;
    border-radius: 3px !important;
    z-index: 99999 !important;
}

div[data-type="t3a/audio-note"]::after {
    content: "" !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    left: 0 !important;
    border: 2px dashed #0080ff !important;
    border-radius: 4px !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

/* Additional selectors to handle nested blocks */
.wp-block[data-do-not-narrate="true"] > .wp-block-group__inner-container,
.block-editor-block-list__block[data-do-not-narrate="true"] > .wp-block-group__inner-container {
    position: relative !important;
    z-index: 2 !important;
}

/* Must Narrate Styles */
.wp-block[data-must-narrate="true"],
.block-editor-block-list__block[data-must-narrate="true"],
div[data-must-narrate="true"] {
    position: relative !important;
    outline: none !important;
    padding: 8px !important;
    background-color: rgba(0, 128, 0, 0.03) !important;
    border-radius: 4px !important;
}

.wp-block[data-must-narrate="true"]::before,
.block-editor-block-list__block[data-must-narrate="true"]::before,
div[data-must-narrate="true"]::before {
    content: "🔊 Must narrate" !important;
    display: block !important;
    position: absolute !important;
    top: -20px !important;
    right: 0 !important;
    background-color: #008000 !important;
    color: white !important;
    padding: 2px 8px !important;
    font-size: 11px !important;
    border-radius: 3px !important;
    z-index: 99999 !important;
}

.wp-block[data-must-narrate="true"]::after,
.block-editor-block-list__block[data-must-narrate="true"]::after,
div[data-must-narrate="true"]::after {
    content: "" !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    left: 0 !important;
    border: 2px dashed #008000 !important;
    border-radius: 4px !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

/* Type 3 Player Block Styles */
.t3a-player-block {
    position: relative !important;
    outline: none !important;
    padding: 8px !important;
    background-color: rgba(0, 128, 255, 0.03) !important;
    border-radius: 4px !important;
}

/* Inspector Controls Styles */
.t3a-player-inspector {
    padding: 16px !important;
}

.t3a-player-block::before {
    content: "🎧 TYPE III AUDIO Player" !important;
    display: block !important;
    position: absolute !important;
    top: -20px !important;
    right: 0 !important;
    background-color: #0080ff !important;
    color: white !important;
    padding: 2px 8px !important;
    font-size: 11px !important;
    border-radius: 3px !important;
    z-index: 99999 !important;
}

.t3a-player-block::after {
    content: "" !important;
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    left: 0 !important;
    border: 2px dashed #0080ff !important;
    border-radius: 4px !important;
    pointer-events: none !important;
    z-index: 1 !important;
}
CSS;
    wp_register_style('t3a-do-not-narrate-editor-css', false);
    wp_enqueue_style('t3a-do-not-narrate-editor-css');
    wp_add_inline_style('t3a-do-not-narrate-editor-css', $editor_css);
});

/**
 * On the front end, wrap blocks in appropriate divs based on their attributes
 */
add_filter('render_block', function($block_content, $block) {
    // Always wrap our custom audio note block in the audio note class
    if ($block['blockName'] === 't3a/audio-note') {
        return '<div class="t3a-audio-note">' . $block_content . '</div>';
    }
    
    // Handle regular blocks with our attributes
    if (!empty($block['attrs']['doNotNarrate'])) {
        return '<div class="t3a-do-not-narrate">' . $block_content . '</div>';
    }
    if (!empty($block['attrs']['mustNarrate'])) {
        return '<div class="t3a-must-narrate">' . $block_content . '</div>';
    }
    if (!empty($block['attrs']['audioNote'])) {
        return '<div class="t3a-audio-note">' . $block_content . '</div>';
    }
    return $block_content;
}, 10, 2);

// Add front-end styles to hide audio notes
add_action('wp_head', function() {
    echo '<style>
        .t3a-audio-note {
            display: none !important;
        }
    </style>';
}); 