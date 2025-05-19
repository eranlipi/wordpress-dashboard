<?php
/*
Template Name: Add Update
*/

// בדיקה שהמשתמש מחובר והוא מנהל
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

get_header();

// קבלת כל התגיות האפשריות
$update_tags = get_terms(array(
    'taxonomy' => 'update_tag',
    'hide_empty' => false,
));
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>עדכון חדש</h5>
                    <button type="button" class="close-btn" onclick="window.location.href='<?php echo get_permalink(get_page_by_path('dashboard')); ?>'">×</button>
                </div>
                <div class="card-body">
                    <form id="add-update-form" method="post">
                        <div class="mb-3">
                            <label for="update_title" class="form-label">כותרת</label>
                            <input type="text" class="form-control" id="update_title" name="update_title" placeholder="הזן כותרת" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="update_tag" class="form-label">תגית</label>
                            <div class="d-flex">
                                <select class="form-select me-2" id="update_tag" name="update_tag">
                                    <option value="">בחר תגית</option>
                                    <?php foreach ($update_tags as $tag) : ?>
                                        <option value="<?php echo $tag->term_id; ?>"><?php echo $tag->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" id="add-new-tag-btn">תגית חדשה</button>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="new-tag-container" style="display: none;">
                            <div class="input-group">
                                <input type="text" class="form-control" id="new_tag_name" placeholder="שם התגית">
                                <input type="text" class="form-control" id="new_tag_color" placeholder="צבע (הקסדצימלי, כגון #007bff)">
                                <button class="btn btn-outline-primary" type="button" id="save-new-tag-btn">שמור תגית</button>
                                <button class="btn btn-outline-secondary" type="button" id="cancel-new-tag-btn">בטל</button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="update_publish_date" class="form-label">תאריך פרסום</label>
                            <input type="date" class="form-control" id="update_publish_date" name="update_publish_date" value="<?php echo date('Y-m-d'); ?>">
                            <small class="form-text text-muted">השאר ריק לפרסום מיידי או הגדר תאריך עתידי לפרסום אוטומטי.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="update_content" class="form-label">תוכן</label>
                            <?php
                            wp_editor('', 'update_content', array(
                                'textarea_name' => 'update_content',
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                                'teeny' => false,
                                'tinymce' => array(
                                    'directionality' => 'rtl',
                                ),
                            ));
                            ?>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='<?php echo get_permalink(get_page_by_path('dashboard')); ?>'">ביטול</button>
                            <button type="submit" class="btn btn-primary">הוסף</button>
                        </div>
                    </form>
                    
                    <div id="add-update-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // הוספת תגית חדשה
    $('#add-new-tag-btn').on('click', function() {
        $('#new-tag-container').slideDown();
    });
    
    $('#cancel-new-tag-btn').on('click', function() {
        $('#new-tag-container').slideUp();
        $('#new_tag_name, #new_tag_color').val('');
    });
    
    $('#save-new-tag-btn').on('click', function() {
        const tagName = $('#new_tag_name').val().trim();
        const tagColor = $('#new_tag_color').val().trim();
        
        if (!tagName) {
            alert('נא להזין שם תגית.');
            return;
        }
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_add_update_tag',
                tag_name: tagName,
                tag_color: tagColor,
                nonce: medmaster_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const newTag = response.data;
                    $('#update_tag').append(new Option(newTag.name, newTag.term_id, true, true));
                    $('#new-tag-container').slideUp();
                    $('#new_tag_name, #new_tag_color').val('');
                } else {
                    alert(response.data.message || 'שגיאה בהוספת תגית חדשה.');
                }
            },
            error: function() {
                alert('שגיאת שרת בהוספת תגית חדשה.');
            }
        });
    });
    
    // שליחת טופס הוספת עדכון
    $('#add-update-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const title = $('#update_title').val().trim();
        const content = tinymce.get('update_content').getContent();
        const tagId = $('#update_tag').val();
        const publishDate = $('#update_publish_date').val();
        
        if (!title) {
            alert('נא להזין כותרת לעדכון.');
            return;
        }
        
        if (!content) {
            alert('נא להזין תוכן לעדכון.');
            return;
        }
        
        // הצגת הודעת טעינה
        $('#add-update-message').html('<div class="alert alert-info">מוסיף עדכון...</div>');
        
        $.ajax({
            url: medmaster_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'medmaster_add_update',
                update_title: title,
                update_content: content,
                update_tag: tagId,
                update_publish_date: publishDate,
                nonce: medmaster_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#add-update-message').html('<div class="alert alert-success">' + response.data.message + '</div>');
                    form[0].reset();
                    tinymce.get('update_content').setContent('');
                    
                    // הפניה ללוח הבקרה אחרי 2 שניות
                    setTimeout(function() {
                        window.location.href = '<?php echo get_permalink(get_page_by_path('dashboard')); ?>';
                    }, 2000);
                } else {
                    $('#add-update-message').html('<div class="alert alert-danger">' + 
                        (response.data.message || 'שגיאה בהוספת העדכון.') + '</div>');
                }
            },
            error: function() {
                $('#add-update-message').html('<div class="alert alert-danger">שגיאת שרת בהוספת העדכון.</div>');
            }
        });
    });
});
</script>

<?php get_footer(); ?>