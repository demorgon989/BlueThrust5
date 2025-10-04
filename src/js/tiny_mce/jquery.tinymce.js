/**
 * TinyMCE 8 jQuery Compatibility Layer
 * Provides backward compatibility for old TinyMCE 3 jQuery syntax
 */
(function($) {
    // jQuery plugin for TinyMCE compatibility
    $.fn.tinymce = function(options) {
        var $this = this;
        options = options || {};
        
        // Fix old TinyMCE 3 script_url filename to TinyMCE 8 filename
        if (options.script_url) {
            options.script_url = options.script_url.replace('tiny_mce.js', 'tinymce.min.js');
        }
        
        // Ensure TinyMCE is loaded
        function initEditor() {
            $this.each(function() {
                var $el = $(this);
                var id = this.id;
                
                // Detect dark theme from options, skin parameter, content CSS, or page body class
                var bodyClass = document.body ? document.body.className : '';
                var contentCss = options.content_css || '';
                console.log('Body class for dark theme detection:', bodyClass);
                console.log('Content CSS:', contentCss);
                
                var isDark = (options.skin && options.skin.indexOf('dark') !== -1) || 
                           (contentCss.indexOf('dark') !== -1) ||
                           (contentCss.indexOf('btcs4') !== -1) ||  // btcs4 is a dark theme
                           (contentCss.indexOf('ghost') !== -1) ||
                           (contentCss.indexOf('battlecity') !== -1) ||
                           (bodyClass.indexOf('dark') !== -1) || 
                           (bodyClass.indexOf('ghost') !== -1) ||
                           (bodyClass.indexOf('battlecity') !== -1);
                
                console.log('Dark theme detected:', isDark);
                
                // Get the base URL from the script_url or calculate it
                var baseUrl = '';
                if (options.script_url) {
                    baseUrl = options.script_url.replace(/\/tinymce\.min\.js.*$/, '');
                } else {
                    // Try to find it from the jquery.tinymce.js script tag
                    var scripts = document.getElementsByTagName('script');
                    for (var i = 0; i < scripts.length; i++) {
                        var src = scripts[i].src;
                        if (src && src.indexOf('jquery.tinymce.js') !== -1) {
                            baseUrl = src.replace(/\/jquery\.tinymce\.js.*$/, '');
                            break;
                        }
                    }
                }
                
                // Determine skin name
                var skinName = isDark ? 'oxide-dark' : 'oxide';
                
                // Convert old TinyMCE 3 options to TinyMCE 8 format
                var modernOptions = {
                    selector: '#' + id,
                    base_url: baseUrl,
                    suffix: '.min',
                    license_key: 'gpl',  // Use open source GPL license
                    promotion: false,  // Disable premium features promotion
                    skin: skinName,
                    skin_url: baseUrl + '/skins/ui/' + skinName,
                    content_css: isDark ? 'dark' : 'default',
                    plugins: '',  // Start with no plugins to test basic buttons
                    toolbar: 'undo redo | bold italic underline strikethrough | alignleft aligncenter alignright',
                    toolbar_mode: 'wrap',
                    toolbar_sticky: false,
                    menubar: false,
                    statusbar: true,
                    branding: false,
                    resize: true,
                    width: '100%',  // Force full width instead of inheriting textarea's width
                    height: 300,
                    min_height: 300,
                    inline: false
                };
                
                console.log('TinyMCE Init Options:', modernOptions);

                // Map old options to new ones
                if (options.content_css) {
                    // Keep the old content_css for compatibility
                    modernOptions.content_css = options.content_css;
                    
                    // Add custom dark mode styling for content area
                    if (isDark) {
                        modernOptions.content_style = 'body { background-color: #1a1a1a; color: #e0e0e0; }';
                    }
                }
                
                if (options.theme_advanced_resizing !== undefined) {
                    modernOptions.resize = options.theme_advanced_resizing;
                }

                // Convert old toolbar format
                if (options.theme_advanced_buttons1) {
                    var toolbar = options.theme_advanced_buttons1
                        .replace(/justifyleft/g, 'alignleft')
                        .replace(/justifycenter/g, 'aligncenter')
                        .replace(/justifyright/g, 'alignright')
                        .replace(/emotions/g, 'emoticons')
                        .replace(/forecolorpicker/g, 'forecolor')
                        .replace(/fontselect/g, '')
                        .replace(/fontsizeselect/g, '')
                        .replace(/quotebbcode/g, 'quotebbcode')  // Keep custom BBCode buttons
                        .replace(/codebbcode/g, 'codebbcode')   // Keep custom BBCode buttons
                        .replace(/,,+/g, ',')  // Remove double commas
                        .replace(/,\s*\|/g, ' |')  // Clean up comma before pipe
                        .replace(/\|\s*,/g, '| ')  // Clean up comma after pipe
                        .replace(/,/g, ' ')  // CRITICAL: Replace ALL commas with spaces for TinyMCE 8!
                        .replace(/\s+/g, ' ')  // Clean up multiple spaces
                        .replace(/\|\s+\|/g, '|')  // Remove empty separators
                        .trim();
                    modernOptions.toolbar = toolbar;
                    console.log('Converted toolbar from old format:', toolbar);
                }

                // Setup function for custom buttons
                modernOptions.setup = function(editor) {
                    console.log('TinyMCE setup function called for editor:', editor);
                    
                    // Add custom Quote button with icon
                    editor.ui.registry.addButton('quotebbcode', {
                        icon: 'quote',
                        text: 'Quote',
                        tooltip: 'Insert Quote [BBCode]',
                        onAction: function() {
                            var selectedText = editor.selection.getContent({format: 'text'});
                            if (selectedText) {
                                editor.selection.setContent('[quote]' + selectedText + '[/quote]');
                            } else {
                                editor.insertContent('[quote][/quote]');
                                // Move cursor between tags
                                var node = editor.selection.getNode();
                                editor.selection.select(node);
                                editor.selection.collapse(false);
                            }
                        }
                    });
                    
                    // Add custom Code button with icon
                    editor.ui.registry.addButton('codebbcode', {
                        icon: 'sourcecode',
                        text: 'Code',
                        tooltip: 'Insert Code [BBCode]',
                        onAction: function() {
                            var selectedText = editor.selection.getContent({format: 'text'});
                            if (selectedText) {
                                editor.selection.setContent('[code]' + selectedText + '[/code]');
                            } else {
                                editor.insertContent('[code][/code]');
                                // Move cursor between tags
                                var node = editor.selection.getNode();
                                editor.selection.select(node);
                                editor.selection.collapse(false);
                            }
                        }
                    });
                    
                    editor.on('init', function() {
                        console.log('TinyMCE editor initialized successfully!');
                        console.log('Editor container:', editor.getContainer());
                        console.log('Editor element:', editor.getElement());
                        
                        // Force container to be visible and full width
                        var container = editor.getContainer();
                        if (container) {
                            container.style.visibility = 'visible';
                            container.style.setProperty('width', '100%', 'important');
                            container.style.setProperty('max-width', '100%', 'important');
                            container.style.setProperty('margin-left', '0', 'important');
                            container.style.setProperty('margin-right', '0', 'important');
                            
                            console.log('Forced container visibility and width to 100%');
                        }
                        
                        // Ensure content is saved to textarea on form submit
                        var textarea = editor.getElement();
                        if (textarea && textarea.form) {
                            var form = textarea.form;
                            // Use addEventListener instead of jQuery
                            form.addEventListener('submit', function() {
                                editor.save();
                                console.log('TinyMCE content saved to textarea on form submit');
                            });
                        }
                    });
                    
                    // Also save on any change
                    editor.on('change', function() {
                        editor.save();
                        
                        // Check if toolbar exists
                        var toolbar = container.querySelector('.tox-toolbar');
                        console.log('Toolbar element:', toolbar);
                        if (toolbar) {
                            console.log('Toolbar display:', window.getComputedStyle(toolbar).display);
                            console.log('Toolbar visibility:', window.getComputedStyle(toolbar).visibility);
                            console.log('Toolbar height:', window.getComputedStyle(toolbar).height);
                        }
                    });
                };

                // Initialize TinyMCE
                tinymce.init(modernOptions);
            });
        }
        
        // Load TinyMCE if not already loaded
        if (typeof tinymce === 'undefined') {
            var script = document.createElement('script');
            
            // Determine the script URL
            if (options.script_url) {
                script.src = options.script_url;
            } else {
                // Extract base path from this script's location
                var scripts = document.getElementsByTagName('script');
                var thisScriptSrc = '';
                for (var i = 0; i < scripts.length; i++) {
                    if (scripts[i].src && scripts[i].src.indexOf('jquery.tinymce.js') !== -1) {
                        thisScriptSrc = scripts[i].src;
                        break;
                    }
                }
                script.src = thisScriptSrc ? 
                           thisScriptSrc.replace('jquery.tinymce.js', 'tinymce.min.js') :
                           '/js/tiny_mce/tinymce.min.js';
            }
            
            console.log('Loading TinyMCE from:', script.src);
            script.onload = initEditor;
            script.onerror = function() {
                console.error('Failed to load TinyMCE from:', script.src);
            };
            document.head.appendChild(script);
        } else {
            initEditor();
        }
        
        return this;
    };
})(jQuery);