<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_quickicon
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$html = HTMLHelper::_('icons.buttons', $buttons);
?>
<?php if (!empty($html)) : ?>
	<nav  class="quick-icons" aria-label="<?php echo Text::_('MOD_QUICKICON_NAV_LABEL'); ?>">
		<div  role="quickicons">
			<ul class="nav flex-wrap row">
				<?php echo $html; ?>
			</ul>
		</div>
	</nav>
<?php endif; ?>
