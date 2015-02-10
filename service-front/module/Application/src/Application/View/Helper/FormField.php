<?php
namespace Application\View\Helper;

use Zend\Form\View\Helper\FormElementErrors;
use Zend\Form\ElementInterface;

class FormField extends FormElementErrors
{
  /**
   * HTML templates
   */
  const TMPL_WRAP 
    = '<div class="group #{error_class}">
      #{inner}
      </div>';
  const TMPL_LABEL 
    = '<label for="#{id}" id="#{id}-label" class="#{label_class}">#{label}#{flag_as}#{errors}</label>
      #{field_type}';
  const TMPL_LABEL_CHECKBOX 
    = '<label for="#{id}" id="#{id}-label" class="#{label_class} checkbox">#{field_type}#{label}#{errors}</label>';
  const TMPL_INPUT 
    = '<input name="#{name}" type="#{type}" id="#{id}" value="#{value}" autocomplete="off"#{maxlength}#{aria_required}>';
  const TMPL_SELECT 
    = '<select name="#{name}" id="#{id}">#{options}</select>';
  const TMPL_CHECKBOX 
    = '<input type="checkbox" name="#{name}" id="#{id}" value="#{value}"#{checked}>';
  const TMPL_FLAG_AS 
    = ' <span#{flag_as_class}>(#{flag_as})</span>';

  /**
   * Generates a form field and wrapper
   *
   * Note: assumes field is required (applying 'required' class to label) unless
   *       a label_class is supplied in $options
   *
   * @param object (Zend form element)
   * @param array - list of options
   * @return string (HTML)
   */
  public function __invoke(ElementInterface $field, $options=array()) {

    // Store variables to prevent multiple method calls
    $type = $this->getType($field);
    $label_class = $this->getPropFromOptions('label_class', $options);
    $maxlength = $this->getPropFromAttr('maxlength', $field);
    $sub_form = $this->getPropFromOptions('sub_form', $options);
    $flag_as = $this->getPropFromOptions('flag_as', $options);
    $flag_as_class = $this->getPropFromOptions('flag_as_class', $options);

    // Insert specific templates based on field type
    switch ($type) {
      case 'checkbox':
        $html = str_replace("#{inner}", self::TMPL_LABEL_CHECKBOX, self::TMPL_WRAP);
        $html = str_replace("#{field_type}", self::TMPL_CHECKBOX, $html);
        break;
      
      case 'select':
        $html = str_replace("#{inner}", self::TMPL_LABEL, self::TMPL_WRAP);
        $html = str_replace("#{field_type}", self::TMPL_SELECT, $html);
        $html = str_replace("#{options}", $this->selectOptions($field), $html);
        break;
      
      case 'date':
        $html = str_replace("#{inner}", self::TMPL_LABEL, self::TMPL_WRAP);
        $html = str_replace(
            "#{field_type}", 
            $this->view->formDate(
                $field->getName(), 
                $field->getValue(), 
                $field->getAttributes()
            ), 
            $html
        );
        break;
      
      default:
        $html = str_replace("#{inner}", self::TMPL_LABEL, self::TMPL_WRAP);
        $html = str_replace("#{field_type}", self::TMPL_INPUT, $html);
        break;
    }
    
    // Replace general values
    $html = str_replace("#{name}", $this->prefixSubform($field->getName(), $sub_form), $html);
    $html = str_replace("#{type}", $type, $html);
    $html = str_replace("#{id}", $this->getId($field, $sub_form), $html);
    $html = str_replace("#{label}", $field->getLabel(), $html);
    $html = str_replace("#{label_class}", $label_class ? $label_class : 'required', $html);
    if ($maxlength) $html = str_replace("#{maxlength}", " maxlength=\"$maxlength\"", $html);

    // 
    if ($flag_as) {
      $flag_as_html = str_replace("#{flag_as}", $flag_as, self::TMPL_FLAG_AS);
      if ($flag_as_class) $flag_as_html = str_replace("#{flag_as_class}", " class=\"$flag_as_class\"", $flag_as_html);
      $html = str_replace("#{flag_as}", $flag_as_html, $html);
    }

    $escaper = new \Zend\Escaper\Escaper('utf-8');
    
    switch ($type) {
      // Checkbox
      case 'checkbox':
        $html = str_replace("#{value}", $escaper->escapeHtml($field->getCheckedValue()), $html);
        $html = str_replace("#{checked}", ($field->isChecked() ? ' checked="checked"' : ''), $html);
        break;
      
      // Don't pre-populate the users passwords
      case 'password':
        break;
      
      default:
        $html = str_replace("#{value}", $escaper->escapeHtml($field->getValue()), $html);
        break;
    }

    // Add errors
    if (count($field->getMessages()) > 0) {
      $html = str_replace("#{error_class}", 'validation', $html);
      $html = str_replace("#{errors}", $this->view->formElementErrors($field), $html);
    }
    
    // Remove unpopulated placeholders
    $html = $this->stripPlaceholders($html);
    
    return $html;
  }

  protected function stripPlaceholders($html) {
      return preg_replace('/#\{[a-z_]+\}/i', '', $html);
  }

  /**
   * Prefix a string with the subform (form array) name
   *
   * E.g. address[town]
   *
   * @param string - name of the field
   * @param string (optional) - name of the subform
   * @return string
   */
  protected function prefixSubform($name, $sub_form=null) {
    return $sub_form ? $sub_form."[$name]" : $name;
  }


  /**
   * Get form field property
   *
   * @param string - property name
   * @param ElementInterface $field
   * @param glue (optional) - defaults to space
   * @return string
   */
  protected function getPropFromAttr($prop, ElementInterface $field) {
    foreach ($field->getAttributes() as $key => $value) {
      if ($prop === $key) {
        return $value;
      }
    }
  }


  /**
   * Get form field property
   *
   * @param string - property name
   * @param string/array
   * @param glue (optional) - defaults to space
   * @return string
   */
  protected function getPropFromOptions($prop, $options, $glue=' ') {
    foreach ($options as $key => $value) {
      if ($key == $prop) {
        return is_array($value) ? implode($glue, $value) : $value;
      }
    }
  }


  /**
   * Generate select box options
   *
   * @param object (Zend form)
   * @return string (HTML)
   */
  protected function selectOptions($field) {

    $current = $field->getValue();
    $html = '';
    $option = '<option value="#{value}"#{selected}>#{label}</option>';
    $selected = ' selected="selected"';

    foreach ($field->getMultiOptions() as $key => $value) {
      $html.= str_replace('#{value}', $key, $option);
      $html = str_replace('#{label}', $value, $html);

      if ($key == $current) $html = str_replace('#{selected}', $selected, $html);
    }

    return $html;
  }


  /**
   * Get the field type
   *
   * @param object (Zend form element)
   * @return string
   */
  protected function getType($field) {
    switch ($field->helper) {
      case 'formCheckbox':
        return 'checkbox';
        break;
      case 'formPassword':
        return 'password';
        break;
      case 'formEmail':
        return 'email';
        break;
      case 'formSelect':
        return 'select';
        break;
      case 'formDate':
        return 'date';
        break;
      default:
        return 'text';
    }
  }


  /**
   * Return a HTML span containing the error for the given field
   * Only returns first error
   *
   * @param object (Zend form element)
   * @return string (HTML)
   */
  protected function fieldErrors($field) {
    if ($this->hasErrors($field)) {
      return ' <span class="validation-message">' . $this->elementError($field) .'</span>';
    }
  }
    
    
}