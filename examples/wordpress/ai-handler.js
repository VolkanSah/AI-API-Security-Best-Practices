jQuery(document).ready(function($) {
    $('#ai-submit').on('click', function(e) {
        e.preventDefault();
        
        const prompt = $('#ai-prompt').val();
        const $button = $(this);
        const $result = $('#ai-result');
        
        $button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: aiConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_request',
                nonce: aiConfig.nonce,
                prompt: prompt
            },
            success: function(response) {
                if (response.success) {
                    $result.html(response.data.response);
                } else {
                    $result.html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $result.html('<p class="error">Request failed. Please try again.</p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Generate');
            }
        });
    });
});
