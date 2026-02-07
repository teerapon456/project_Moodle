<?php

/**
 * Reusable UI Components for CarBooking Module
 * Modern, component-based architecture
 */

/**
 * Render Page Header with tabs
 * @param string $title - Page title
 * @param array $tabs - Array of tabs [['label' => '', 'href' => '', 'active' => bool, 'canView' => bool]]
 */
function renderPageHeader($title, $tabs = [])
{
    echo '<div class="cb-page-title" style="align-items:flex-start;">';
    echo '<div>';
    echo '<h2 class="cb-heading">' . htmlspecialchars($title) . '</h2>';

    if (!empty($tabs)) {
        echo '<div class="cb-tab-bar">';
        foreach ($tabs as $tab) {
            $canView = isset($tab['canView']) ? $tab['canView'] : true;
            if (!$canView) continue;

            $active = isset($tab['active']) && $tab['active'] ? 'active' : '';
            $href = isset($tab['href']) ? $tab['href'] : '#';
            echo '<a class="cb-tab-link ' . $active . '" href="' . htmlspecialchars($href) . '">';
            echo htmlspecialchars($tab['label']);
            echo '</a>';
        }
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Render Card Component
 * @param string $title - Card title
 * @param string $content - Card content (HTML)
 * @param string $headerActions - Optional header actions (HTML)
 * @param array $options - Optional card options ['class' => '', 'style' => '']
 */
function renderCard($title = '', $content = '', $headerActions = '', $options = [])
{
    $class = isset($options['class']) ? $options['class'] : '';
    $style = isset($options['style']) ? $options['style'] : '';

    echo '<div class="cb-card ' . $class . '" style="' . $style . '">';

    if ($title || $headerActions) {
        echo '<div class="cb-card-header">';
        if ($title) {
            echo '<h3 class="cb-card-title">' . htmlspecialchars($title) . '</h3>';
        }
        if ($headerActions) {
            echo '<div class="cb-card-actions">' . $headerActions . '</div>';
        }
        echo '</div>';
    }

    echo '<div class="cb-card-content">';
    echo $content;
    echo '</div>';

    echo '</div>';
}

/**
 * Render Status Badge
 * @param string $status - Status value
 * @param string $label - Optional custom label (defaults to status)
 */
function renderStatusBadge($status, $label = null)
{
    $statusLabels = [
        'pending' => 'รอดำเนินการ',
        'pending_supervisor' => 'รอหัวหน้าอนุมัติ',
        'pending_manager' => 'รอสายงาน IPCD อนุมัติ',
        'approved' => 'อนุมัติ',
        'rejected' => 'ไม่อนุมัติ',
        'cancelled' => 'ยกเลิก',
        'completed' => 'เสร็จสิ้น'
    ];

    $displayLabel = $label ?? ($statusLabels[$status] ?? $status);
    echo '<span class="cb-badge cb-status-' . htmlspecialchars($status) . '">';
    echo htmlspecialchars($displayLabel);
    echo '</span>';
}

/**
 * Render Button
 * @param string $text - Button text
 * @param array $options - ['type' => 'primary|secondary|danger', 'id' => '', 'class' => '', 'onclick' => '', 'icon' => '', 'disabled' => false]
 */
function renderButton($text, $options = [])
{
    $type = isset($options['type']) ? $options['type'] : 'primary';
    $id = isset($options['id']) ? ' id="' . htmlspecialchars($options['id']) . '"' : '';
    $class = isset($options['class']) ? $options['class'] : '';
    $onclick = isset($options['onclick']) ? ' onclick="' . htmlspecialchars($options['onclick']) . '"' : '';
    $icon = isset($options['icon']) ? $options['icon'] : '';
    $disabled = isset($options['disabled']) && $options['disabled'] ? ' disabled' : '';

    echo '<button class="cb-btn cb-btn-' . $type . ' ' . $class . '"' . $id . $onclick . $disabled . '>';
    if ($icon) {
        echo '<i class="' . htmlspecialchars($icon) . '"></i> ';
    }
    echo htmlspecialchars($text);
    echo '</button>';
}

/**
 * Render Table
 * @param array $headers - Array of header labels
 * @param array $rows - Array of row data
 * @param array $options - ['emptyMessage' => '', 'rowCallback' => callable]
 */
function renderTable($headers, $rows, $options = [])
{
    $emptyMessage = isset($options['emptyMessage']) ? $options['emptyMessage'] : 'ไม่มีข้อมูล';
    $rowCallback = isset($options['rowCallback']) ? $options['rowCallback'] : null;

    echo '<div class="cb-table-wrapper">';
    echo '<table class="cb-table">';
    echo '<thead><tr>';

    foreach ($headers as $header) {
        $width = isset($header['width']) ? ' style="width:' . htmlspecialchars($header['width']) . ';"' : '';
        $label = isset($header['label']) ? $header['label'] : $header;
        echo '<th' . $width . '>' . htmlspecialchars($label) . '</th>';
    }

    echo '</tr></thead>';
    echo '<tbody>';

    if (empty($rows)) {
        $colspan = count($headers);
        echo '<tr><td colspan="' . $colspan . '" class="cb-table-empty">' . htmlspecialchars($emptyMessage) . '</td></tr>';
    } else {
        foreach ($rows as $row) {
            if ($rowCallback && is_callable($rowCallback)) {
                $rowCallback($row);
            } else {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . $cell . '</td>';
                }
                echo '</tr>';
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Render Form Field
 * @param string $type - Field type (text, email, textarea, select, datetime-local)
 * @param string $label - Field label
 * @param string $id - Field ID
 * @param array $options - ['placeholder' => '', 'required' => bool, 'value' => '', 'options' => [] for select, 'rows' => for textarea]
 */
function renderFormField($type, $label, $id, $options = [])
{
    $placeholder = isset($options['placeholder']) ? $options['placeholder'] : '';
    $required = isset($options['required']) && $options['required'] ? ' required' : '';
    $value = isset($options['value']) ? $options['value'] : '';
    $customClass = isset($options['class']) ? $options['class'] : '';

    echo '<div class="cb-form-row">';
    echo '<label for="' . htmlspecialchars($id) . '" class="cb-label">' . htmlspecialchars($label) . '</label>';

    if ($type === 'textarea') {
        $rows = isset($options['rows']) ? $options['rows'] : 3;
        echo '<textarea id="' . htmlspecialchars($id) . '" class="cb-textarea ' . $customClass . '" rows="' . $rows . '" placeholder="' . htmlspecialchars($placeholder) . '"' . $required . '>';
        echo htmlspecialchars($value);
        echo '</textarea>';
    } elseif ($type === 'select') {
        $selectOptions = isset($options['options']) ? $options['options'] : [];
        echo '<select id="' . htmlspecialchars($id) . '" class="cb-select ' . $customClass . '"' . $required . '>';
        foreach ($selectOptions as $opt) {
            $optValue = isset($opt['value']) ? $opt['value'] : $opt;
            $optLabel = isset($opt['label']) ? $opt['label'] : $opt;
            $selected = $optValue == $value ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($optValue) . '"' . $selected . '>' . htmlspecialchars($optLabel) . '</option>';
        }
        echo '</select>';
    } else {
        echo '<input type="' . htmlspecialchars($type) . '" id="' . htmlspecialchars($id) . '" class="cb-input ' . $customClass . '" placeholder="' . htmlspecialchars($placeholder) . '" value="' . htmlspecialchars($value) . '"' . $required . '>';
    }

    echo '</div>';
}

/**
 * Render Modal
 * @param string $id - Modal ID
 * @param string $title - Modal title
 * @param string $content - Modal content (HTML)
 * @param string $footer - Modal footer content (HTML)
 * @param array $options - ['size' => 'small|medium|large', 'class' => '']
 */
function renderModal($id, $title, $content, $footer = '', $options = [])
{
    $size = isset($options['size']) ? $options['size'] : 'medium';
    $class = isset($options['class']) ? $options['class'] : '';

    echo '<div id="' . htmlspecialchars($id) . '" class="cb-modal-overlay" style="display:none;">';
    echo '<div class="cb-modal cb-modal-' . $size . ' ' . $class . '">';

    echo '<div class="cb-modal-header">';
    echo '<h3 class="cb-modal-title">' . htmlspecialchars($title) . '</h3>';
    echo '<button class="cb-modal-close" data-modal="' . htmlspecialchars($id) . '" aria-label="ปิด">';
    echo '<i class="ri-close-line"></i>';
    echo '</button>';
    echo '</div>';

    echo '<div class="cb-modal-body">';
    echo $content;
    echo '</div>';

    if ($footer) {
        echo '<div class="cb-modal-footer">';
        echo $footer;
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Render Passenger List Item (for booking form)
 * @param array $passenger - ['name' => '', 'email' => '', 'user_id' => '']
 * @param int $index - Index in the list
 */
function renderPassengerItem($passenger, $index)
{
    $displayName = isset($passenger['name']) && $passenger['name'] ? $passenger['name'] : $passenger['email'];
    echo '<div class="cb-passenger-item" data-index="' . $index . '">';
    echo '<div class="cb-passenger-name">' . htmlspecialchars($displayName) . '</div>';
    echo '<button type="button" class="cb-btn cb-btn-sm cb-btn-danger" onclick="BookingForm.removePassenger(' . $index . ')">ลบ</button>';
    echo '</div>';
}
