# Changes in theme layout

1. remove header.panel.wrapper -- responsible for black background top hearder
2. remove header-wrapper -- responsible for black background Header
3. remove navigation.sections - Responsible for rendering navbar
4. Add header-pharmacy-block - Add custom block as header
5. removing newsletter from layout

# <block class="Magento\Newsletter\Block\Subscribe" name="form.subscribe" as="subscribe" before="-" template="Magento_Newsletter::subscribe.phtml" ifconfig="newsletter/general/active"/>  -- for reference

# changes in theme tempalete

1. Add header and footer phtml file to override parent theme