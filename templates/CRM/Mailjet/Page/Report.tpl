{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<fieldset>
<legend>{ts}Mailjet STATS{/ts}</legend>
{if $mailjet_stats}
  {strip}
  <table class="crm-info-panel">
    <tr>
      <td class="label">{ts}Spamass Score{/ts}</td>
      <td>{$mailjet_stats.spamass_score}</td>
    </tr>
    <tr>
      <td class="label">{ts}Count Messages{/ts}</td>
      <td>{$mailjet_stats.cnt_messages}</td>
    </tr>
     <tr>
      <td class="label">{ts}Delivered{/ts}</td>
      <td>{$mailjet_stats.delivered}</td>
    </tr>
     <tr>
      <td class="label">{ts}Opened{/ts}</td>
      <td>{$mailjet_stats.opened}</td>
    </tr>
     <tr>
      <td class="label">{ts}Clicked{/ts}</td>
      <td>{$mailjet_stats.clicked}</td>
    </tr>
   <tr>
      <td class="label">{ts}Bounce{/ts}</td>
      <td>{$mailjet_stats.bounce}</td>
    </tr>
       <tr>
      <td class="label">{ts}Spam{/ts}</td>
      <td>{$mailjet_stats.spam}</td>
    </tr>
       <tr>
      <td class="label">{ts}Unsub{/ts}</td>
      <td>{$mailjet_stats.unsub} </td>
    </tr>
       <tr>
      <td class="label">{ts}Blocked{/ts}</td>
      <td>{$mailjet_stats.blocked}</td>
    </tr>
       <tr>
      <td class="label">{ts}Queued{/ts}</td>
      <td>{$mailjet_stats.queued}</td>
    </tr>
       <tr>
      <td class="label">{ts}Total{/ts}</td>
      <td>{$mailjet_stats.total}</td>
    </tr>
       <tr>
      <td class="label">{ts}CTO{/ts}</td>
      <td>{$mailjet_stats.cto} ({$mailjet.cto|string_format:"%0.2f"}%)</td>
    </tr>
    <tr>
      <td class="label">{ts}Delivered Rate{/ts}</td>
      <td>{$mailjet_stats.delivered_rate} ({$mailjet_stats.delivered_rate|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}Queued Rate{/ts}</td>
      <td>{$mailjet_stats.queued_rate} ({$mailjet_stats.queued_rate|string_format:"%0.2f"}%)</td>
    </tr>
    <tr>
      <td class="label">{ts}Opened Rate{/ts}</td>
      <td>{$mailjet_stats.opened_rate} ({$mailjet_stats.opened_rate|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}Clicked Rate{/ts}</td>
      <td>{$mailjet_stats.clicked_rate} ({$mailjet_stats.clicked_rate|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}CTDR{/ts}</td>
      <td>{$mailjet_stats.ctdr} ({$mailjet_stats.ctdr|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}Bounce Rate{/ts}</td>
      <td>{$mailjet_stats.bounce_rate} ({$mailjet_stats.bounce_rate|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}Spam Rate{/ts}</td>
      <td>{$mailjet_stats.spam_rate} ({$mailjet_stats.spam_rate|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}Blocked Rate{/ts}</td>
      <td>{$mailjet_stats.blocked_rate} ({$mailjet_stats.blocked_rate|string_format:"%0.2f"}%)</td>
    </tr>
       <tr>
      <td class="label">{ts}Unsub Rate{/ts}</td>
      <td>{$mailjet_stats.unsub_rate} ({$mailjet_stats.unsub_rate|string_format:"%0.2f"}%)</td>
    </tr>
    <tr>
      <td class="label">{ts}Failure Rate{/ts}</td>
      <td>{$mailjet_stats.failure_rate} ({$mailjet_stats.failure_rate|string_format:"%0.2f"}%)</td>
    </tr>
    <tr>
      <td class="label">{ts}Average openned Delay{/ts}</td>
      <td>{$mailjet_stats.avg_opened_delay} </td>
    </tr>
       <tr>
      <td class="label">{ts}Average Opanned Rate{/ts}</td>
      <td>{$mailjet_stats.avg_opened_rate} </td>
    </tr>
     <tr>
      <td class="label">{ts}Average Clicked Rate{/ts}</td>
      <td>{$mailjet_stats.avg_clicked_rate} </td>
    </tr>
       <tr>
      <td class="label">{ts}Average Clicked Delay{/ts}</td>
      <td>{$mailjet_stats.avg_clicked_delay} </td>
    </tr>
  </table>
  {/strip}
{else}
    <div class="messages status no-popup">
        {ts}<strong>Mailjet STATS is not avaliable.</strong> .{/ts}
    </div>
{/if}
</fieldset>



