<?php

namespace PlebWooCommerceShippingRulesets\Models;

use PlebWooCommerceShippingRulesets\Models\Rule;
use PlebWooCommerceShippingRulesets\Contracts\RulesetInterface;

class Ruleset implements RulesetInterface
{
    private $id;
    protected $name;
    protected $cost = '';
    protected $order = null;
    protected $rules = [];

    private function __construct()
    {
    }

    public static function createFromArray(array $rulesetArray = []): self
    {
        $rulesetArray = array_merge([
            'id'      => $id = uniqid(),
            'name'    => __("Ruleset", 'pleb').' #'.$id,
            'cost'    => '',
            'order'   => null,
            'rules'   => [],
        ], $rulesetArray);

        $instance = new static();
        $instance->setId($rulesetArray['id']);
        $instance->setName($rulesetArray['name']);
        $instance->setCost($rulesetArray['cost']);
        $instance->setOrder($rulesetArray['order']);

        if (isset($rulesetArray['rules']) && is_array($rulesetArray['rules'])) {
            foreach ($rulesetArray['rules'] as $ruleArray) {
                $rule = Rule::createFromArray($ruleArray);
                $instance->addRule($rule);
            }
        }

        return $instance;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        if (empty($this->id)) {
            return uniqid();
        }
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        if(empty($this->name)) {
            return __("Ruleset", 'pleb').' #'.$this->getId();
        }
        return $this->name;
    }

    public function setCost(string $cost): self
    {
        $this->cost = $cost;
        return $this;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function setOrder(mixed $order): self
    {
        if(!$order=='default' && !is_null($order)){
            $this->order = intval($order);
        }else{
            $this->order = $order;
        }
        return $this;
    }

    public function getOrder(): mixed
    {
        return $this->order;
    }

    public function addRule(Rule $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function isDefault(): bool
    {
        return $this->order=='default';
    }

    public function htmlRender(string $fieldKey): string
    {
        ob_start();

        ?><div class="postbox pleb_ruleset">

            <input type="hidden" name="<?php echo esc_attr($fieldKey); ?>[<?php echo $this->getId(); ?>][id]" value="<?php echo $this->getId(); ?>">
            <input type="hidden" name="<?php echo esc_attr($fieldKey); ?>[<?php echo $this->getId(); ?>][order]" value="<?php echo esc_attr($this->getOrder()); ?>">

            <div class="postbox-header pleb_title_input_wrapper" style="padding:4px 0;">
                <h2 class="hndle" title="<?php esc_attr_e("Move up/down to change ruleset priority", 'pleb'); ?>">
                    <span>
                        <span class="dashicons dashicons-move"></span>
                        <?php echo $this->getName(); ?>
                    </span>
                </h2>
                
                <div class="pleb_input_wrapper" style="display:none;height:30px;padding:3px 12px;">
                    <input type="text" name="<?php echo esc_attr($fieldKey); ?>[<?php echo $this->getId(); ?>][name]" value="<?php echo esc_attr($this->getName()); ?>" placeholder="<?php esc_attr_e("Ruleset name", "pleb"); ?>" required>
                </div>
                
                <div class="handle-actions" style="padding-right:12px;">
                    <button class="button button-small pleb_edit_ruleset_button">
                        <span class="button_dynamic_action"><?php _e("Edit", 'pleb'); ?></span>
                        <span class="button_dynamic_action" style="display:none;"><?php _e("Stop editing", 'pleb'); ?></span>
                    </button>

                    <div class="plugins" style="float:right;padding-top:5px;padding-left:5px;">
                        <a href="#" class="delete pleb_ruleset_delete" data-ruleset_id="<?php echo $this->getId(); ?>" data-confirm="<?php esc_attr_e("Are you sure to delete this ruleset and all of its rules?", 'pleb'); ?>" style="text-decoration:none;font-size:11px;"><?php _e("Delete ruleset", 'pleb'); ?></a>
                    </div>
                    
                </div>
            </div>

            <div class="postbox-header" style="padding:8px 12px;justify-content:flex-start;">
                
                <label for="" style="display:block;font-weight:600;padding-right:5px;white-space:nowrap;">
                    <?php esc_attr_e("Price to apply:", 'pleb'); ?>
                </label>

                <input type="text" name="<?php echo esc_attr($fieldKey); ?>[<?php echo $this->getId(); ?>][cost]" value="<?php echo $this->getCost(); ?>" class="" placeholder="<?php esc_attr_e("", 'pleb'); ?>" />

                <?php echo wc_help_tip(
                    sprintf(
                        __("Works the same as %s setting field", 'pleb'), 
                        '<b>'.__('Base price', 'pleb').'</b>'
                    ), true
                ); ?>

            </div>

            <div class="inside" style="margin-bottom:0;">

                <?php $rules = $this->getRules(); ?>

                <div class="pleb_no_ruleset_rule_notice notice notice-info inline text-center notice-alt" style="margin:10px 0;<?php if(!empty($rules)) {
                    echo 'display:none;';
                } ?>"><p><span class="dashicons dashicons-dismiss"></span> <?php _e("No rule in this ruleset yet.", 'pleb'); ?></p></div>

                <table class="widefat plugins ruleset_rules" style="margin:10px 0;<?php if(empty($rules)) {
                    echo 'display:none;';
                } ?>">
                    <?php foreach($rules as $rule): ?>
                        <?php echo $rule->htmlRender($fieldKey.'['.$this->getId().'][rules]');?>
                    <?php endforeach; ?>
                </table>
                
                <button type="button" class="button pleb_ruleset_add_rule_button" data-field_key="<?php echo $fieldKey.'['.$this->getId().'][rules]'; ?>"><?php _e("Add new rule", 'pleb'); ?></button>

            </div>
        </div><?php

        return ob_get_clean();
    }

    public function matchToWooCommercePackageArray(array $package = []): bool
    {
        $allRulesSuccess = true;
        
        $rules = $this->getRules();
        if( !empty($rules) ){
            foreach($rules as $rule){
                $ruleSuccess = $rule->matchToWooCommercePackageArray($package);
                if(!$ruleSuccess){
                    $allRulesSuccess = false;
                }
            }
        }

        return $allRulesSuccess;
    }
}
