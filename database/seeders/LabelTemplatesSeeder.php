<?php

namespace Database\Seeders;

use App\Models\LabelTemplate;
use Illuminate\Database\Seeder;

class LabelTemplatesSeeder extends Seeder
{
    /**
     * Seed system-level default label templates (user_id = null).
     * Shops receive copies when they open the templates list.
     */
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            LabelTemplate::updateOrCreate(
                [
                    'user_id' => null,
                    'name' => $template['name'],
                ],
                [
                    'type' => 'default',
                    'content' => $template['content'],
                ]
            );
        }
    }

    private function templates(): array
    {
        return [
            [
                'name' => 'Classic Shipping Label',
                'content' => $this->classicShippingLabel(),
            ],
            [
                'name' => 'Compact Shipping Label',
                'content' => $this->compactShippingLabel(),
            ],
            [
                'name' => 'Packing Slip Label',
                'content' => $this->packingSlipLabel(),
            ],
            [
                'name' => 'Barcode Focus Label',
                'content' => $this->barcodeFocusLabel(),
            ],
        ];
    }

    private function classicShippingLabel(): string
    {
        return <<<'HTML'
<div style="font-family: DejaVu Sans, Arial, sans-serif; color: #1a1a1a; width: 100%;">
  <table width="100%" cellpadding="0" cellspacing="0" style="border: 2px solid #1a1a1a; border-collapse: collapse;">
    <tr>
      <td colspan="2" style="background: #1a1a1a; color: #ffffff; padding: 12px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="font-size: 16px; font-weight: bold; letter-spacing: 0.5px;">SHIPPING LABEL</td>
            <td align="right" style="font-size: 13px;">Order #{order_number}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td width="50%" valign="top" style="padding: 14px 16px; border-right: 1px solid #d0d0d0;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #666666; margin-bottom: 6px;">From</div>
        <div style="font-size: 14px; font-weight: bold; margin-bottom: 4px;">{from_name}</div>
        <div style="font-size: 12px; line-height: 1.45;">
          {from_street1}<br>
          {from_city}, {from_state} {from_zip}<br>
          {from_country}<br>
          {from_phone}
        </div>
      </td>
      <td width="50%" valign="top" style="padding: 14px 16px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #666666; margin-bottom: 6px;">Ship To</div>
        <div style="font-size: 14px; font-weight: bold; margin-bottom: 4px;">{to_name}</div>
        <div style="font-size: 12px; line-height: 1.45;">
          {to_street1}<br>
          {to_city}, {to_state} {to_zip}<br>
          {to_country}<br>
          {to_phone}
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="padding: 12px 16px; border-top: 1px solid #d0d0d0; background: #f7f7f7;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="font-size: 11px;"><strong>Date:</strong> {order_date}</td>
            <td style="font-size: 11px;"><strong>Tracking:</strong> {tracking_number}</td>
            <td align="right" style="font-size: 11px;"><strong>Total:</strong> {total_price}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="2" style="padding: 12px 16px; border-top: 1px solid #d0d0d0;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #666666; margin-bottom: 6px;">Items</div>
        <div style="font-size: 12px; line-height: 1.5;">{items}</div>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center" style="padding: 16px; border-top: 1px solid #d0d0d0;">
        <img src="{bar_code}" alt="Barcode" style="max-width: 280px; height: 55px;">
        <div style="font-size: 11px; margin-top: 6px; letter-spacing: 1px;">{order_number}</div>
      </td>
    </tr>
  </table>
</div>
HTML;
    }

    private function compactShippingLabel(): string
    {
        return <<<'HTML'
<div style="font-family: DejaVu Sans, Arial, sans-serif; color: #222222; width: 100%;">
  <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #333333; border-collapse: collapse;">
    <tr>
      <td style="padding: 10px 12px; border-bottom: 2px solid #333333;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="font-size: 15px; font-weight: bold;">#{order_number}</td>
            <td align="right" style="font-size: 11px; color: #555555;">{order_date}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding: 12px;">
        <div style="font-size: 9px; text-transform: uppercase; color: #777777; margin-bottom: 4px;">Deliver To</div>
        <div style="font-size: 15px; font-weight: bold; margin-bottom: 3px;">{to_name}</div>
        <div style="font-size: 12px; line-height: 1.4;">
          {to_street1}<br>
          {to_city}, {to_state} {to_zip}<br>
          {to_country}
        </div>
      </td>
    </tr>
    <tr>
      <td style="padding: 8px 12px; background: #f4f4f4; border-top: 1px solid #dddddd; font-size: 11px;">
        <strong>From:</strong> {from_name} · {from_city}, {from_state}
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 12px; border-top: 1px solid #dddddd; font-size: 11px;">
        <strong>Item:</strong> {item_name}<br>
        <span style="color: #555555;">{items}</span>
      </td>
    </tr>
    <tr>
      <td align="center" style="padding: 12px; border-top: 1px solid #dddddd;">
        <img src="{bar_code}" alt="Barcode" style="max-width: 260px; height: 48px;">
      </td>
    </tr>
  </table>
</div>
HTML;
    }

    private function packingSlipLabel(): string
    {
        return <<<'HTML'
<div style="font-family: DejaVu Sans, Arial, sans-serif; color: #1f1f1f; width: 100%;">
  <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
    <tr>
      <td style="padding-bottom: 10px; border-bottom: 3px solid #1f1f1f;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <div style="font-size: 18px; font-weight: bold;">Packing Slip</div>
              <div style="font-size: 11px; color: #666666; margin-top: 2px;">Thank you for your order</div>
            </td>
            <td align="right">
              <div style="font-size: 12px;"><strong>Order</strong> #{order_number}</div>
              <div style="font-size: 11px; color: #666666;">{order_date}</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding-top: 14px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td width="48%" valign="top" style="padding-right: 12px;">
              <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888888; margin-bottom: 6px;">Sold By</div>
              <div style="font-size: 13px; font-weight: bold;">{from_name}</div>
              <div style="font-size: 11px; line-height: 1.45; color: #444444;">
                {from_street1}<br>
                {from_city}, {from_state} {from_zip}<br>
                {from_country}<br>
                {from_email}
              </div>
            </td>
            <td width="48%" valign="top" style="padding-left: 12px; border-left: 1px solid #e5e5e5;">
              <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888888; margin-bottom: 6px;">Ship To</div>
              <div style="font-size: 13px; font-weight: bold;">{to_name}</div>
              <div style="font-size: 11px; line-height: 1.45; color: #444444;">
                {to_street1}<br>
                {to_city}, {to_state} {to_zip}<br>
                {to_country}<br>
                {to_phone}
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding-top: 16px;">
        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
          <tr style="background: #f0f0f0;">
            <td style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid #cccccc;"><strong>Items</strong></td>
            <td align="right" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; border-bottom: 1px solid #cccccc;"><strong>Amount</strong></td>
          </tr>
          <tr>
            <td style="font-size: 12px; border-bottom: 1px solid #eeeeee; line-height: 1.5;">{items}</td>
            <td align="right" style="font-size: 12px; border-bottom: 1px solid #eeeeee; white-space: nowrap;">{total_price}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding-top: 14px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="font-size: 11px; color: #555555;">
              <strong>Tracking:</strong> {tracking_number}
            </td>
            <td align="right">
              <img src="{bar_code}" alt="Barcode" style="max-width: 220px; height: 42px;">
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
HTML;
    }

    private function barcodeFocusLabel(): string
    {
        return <<<'HTML'
<div style="font-family: DejaVu Sans, Arial, sans-serif; color: #111111; width: 100%; text-align: center;">
  <table width="100%" cellpadding="0" cellspacing="0" style="border: 2px solid #111111; border-collapse: collapse;">
    <tr>
      <td style="padding: 14px 16px; border-bottom: 1px solid #111111;">
        <div style="font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #555555;">ShipGrow Label</div>
        <div style="font-size: 20px; font-weight: bold; margin-top: 4px;">#{order_number}</div>
      </td>
    </tr>
    <tr>
      <td style="padding: 18px 16px;">
        <img src="{bar_code}" alt="Barcode" style="max-width: 320px; height: 70px;">
        <div style="font-size: 12px; margin-top: 8px; letter-spacing: 1.5px;">{tracking_number}</div>
      </td>
    </tr>
    <tr>
      <td style="padding: 12px 16px; border-top: 1px solid #111111; text-align: left;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td width="50%" valign="top">
              <div style="font-size: 9px; text-transform: uppercase; color: #777777;">From</div>
              <div style="font-size: 12px; font-weight: bold;">{from_name}</div>
              <div style="font-size: 11px;">{from_city}, {from_state}</div>
            </td>
            <td width="50%" valign="top">
              <div style="font-size: 9px; text-transform: uppercase; color: #777777;">To</div>
              <div style="font-size: 12px; font-weight: bold;">{to_name}</div>
              <div style="font-size: 11px;">{to_street1}</div>
              <div style="font-size: 11px;">{to_city}, {to_state} {to_zip}</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding: 10px 16px; background: #f5f5f5; border-top: 1px solid #dddddd; text-align: left; font-size: 11px;">
        <strong>Item:</strong> {item_name} &nbsp;|&nbsp; <strong>Date:</strong> {order_date} &nbsp;|&nbsp; <strong>Total:</strong> {total_price}
      </td>
    </tr>
  </table>
</div>
HTML;
    }
}
