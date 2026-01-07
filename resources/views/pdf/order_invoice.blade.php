@extends('pdf.layout')
@section('title', 'Invoice')
@section('css')

@endsection

@section('content')
    <table class="table">
        <tr>
            <td style="width: 50%;vertical-align: top;border: none;border-left: 6px solid #EA6A39">
                <table class="table">
                    <tr>
                        <td style="vertical-align: top; text-align: left;border: none;">
                            <strong style="font-size: 20px; color: #000000;">Invoice To</strong><br>
                            <p style="font-size: 14px;color: black;line-height: 20px;">
                                {{ $order->customer_full_name ?? '' }}<br>
                                <span style="font-weight: bold;">{{ $order->mobile ?? '' }}</span><br>
                                {{ $order->city_town ?? '' }} {{ $order->post_code ?? '' }}<br>
                                {{ $order->address ?? '' }}<br>
                                {{ 'Bangladesh' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%;border: none;text-align: right;vertical-align: top">
                <table class="table">
                    <tbody>
                        <tr>
                            <td style="border: none;padding: 2px 4px;vertical-align: top;">Invoice No.</td>
                            <td style="border: none;padding: 2px 4px;">:</td>
                            <td style="border: none;padding: 2px 4px;"><strong>{{ $order->invoice ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="border: none;padding: 2px 4px;">Invoice Date</td>
                            <td style="border: none;padding: 2px 4px;">:</td>
                            <td style="border: none;padding: 2px 4px;">
                                {{ date('d M, Y', strtotime($order->created_at)) }}</td>
                        </tr>
                        <tr>
                            <td style="border: none;padding: 2px 4px;">Order No.</td>
                            <td style="border: none;padding: 2px 4px;">:</td>
                            <td style="border: none;padding: 2px 4px;">{{ $order->order_number ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border: none;padding: 2px 4px;">Payment Method</td>
                            <td style="border: none;padding: 2px 4px;">:</td>
                            <td style="border: none;padding: 2px 4px;">{{ $order->payment_method_name ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border: none;padding: 2px 4px;">Payment Status</td>
                            <td style="border: none;padding: 2px 4px;">:</td>
                            <td style="border: none;padding: 2px 4px;">{{ ucFirst($order->payment_status) ?? '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="vertical-align: top;border: none;">
                <td style="text-align: left;vertical-align: top;border: none;">
                    <img src="{{ asset($order->qr_image_path) }}" style="width: 120px;height: 120px;">
                </td>
            </td>
        </tr>
    </table>

    <table class="table items-table" style="margin-top: 10px;">
        <thead style="display: table-header-group;">
            <tr style="background-color: #000000;">
                <th style="color: white; white-space: nowrap; text-align: center;">SKU</th>
                <th style="color: white; white-space: nowrap; text-align: left;">Item</th>
                <th style="color: white; white-space: nowrap; text-align: center;">Item price</th>
                <th style="color: white; white-space: nowrap; text-align: center;">Qty</th>
                <th style="color: white; white-space: nowrap; text-align: center;">Item Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rowCount = 0; @endphp
            @foreach ($order->order_products as $order_product)
                @php $rowCount++; @endphp
                <tr>
                    <td style="text-align: center;">{{ $order_product->product->product_code ?? '' }}</td>
                    <td style="text-align: left;">
                        {{ $order_product->product->name ?? '' }} <br>
                        <small>Size: {{ $order_product->size ?? 'N/A' }}</small>
                        <small>Color: {{ $order_product->color ?? 'N/A' }}</small>
                    </td>
                    <td style="text-align: center;">{{ $order_product->item_order_price ?? '' }} BDT</td>
                    <td style="text-align: center;">{{ $order_product->quantity ?? '' }}</td>
                    <td style="text-align: center;">{{ $order_product->item_total_order_price }} BDT</td>
                </tr>
                {{-- Force page break after every 10 rows --}}
                @if ($rowCount % 10 == 0)
        </tbody>
    </table>
    <div style="page-break-after: always;"></div>
    <table class="table items-table" style="margin-top: 10px;">
        <thead style="display: table-header-group;">
            <tr style="background-color: #000000;">
                <th style="color: white; white-space: nowrap; text-align: center;">SKU</th>
                <th style="color: white; white-space: nowrap; text-align: left;">Name</th>
                <th style="color: white; white-space: nowrap; text-align: center;">Item price</th>
                <th style="color: white; white-space: nowrap; text-align: center;">Qty</th>
                <th style="color: white; white-space: nowrap; text-align: center;">Item Total</th>
            </tr>
        </thead>
        <tbody>
            @endif
            @endforeach
        </tbody>
    </table>


    <table class="table" style="margin-top: 30px;">
        <thead>
            <tr style="background-color: #000000">
                <th style="color: white;text-align: center;">Net Amount</th>
                <th style="color: white;text-align: center;">Vat</th>
                <th style="color: white;text-align: center;">Shipping Fee</th>
                <th style="color: white;text-align: center;">Service Charge</th>
                <th style="color: white;text-align: center;">Coupon</th>
                <th style="color: white;text-align: center;">Coupon Discount</th>
                <th style="color: white;text-align: center;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">{{ numberFormat($order->total_item_order_price, 2) ?? 0.0 }} BDT</td>
                <td style="text-align: center;">{{ $order->total_vat ?? 0.0 }} BDT</td>
                <td style="text-align: center;">(+) {{ $order->shipping_fee ?? 0.0 }} BDT</td>
                <td style="text-align: center;">(+) {{ $order->service_charge ?? 0.0 }} BDT</td>
                <td style="text-align: center;">{{ $order->coupon_code ?? 'N/A' }}DESTD</td>
                <td style="text-align: center;">(-) {{ $order->coupon_discount_amount ?? 0.0 }} BDT</td>
                <td style="text-align: center;">{{ $order->total_order_price ?? 0.0 }} BDT</td>
            </tr>
        </tbody>
    </table>
    {{-- <div class="comment-box">
        <p>Comment: {{ $order->comment ?? '' }}</p>
    </div>
    <div class="message-box">
        <small style="font-size: 13px;">In case of late payment, 8% interest per started month and a reminder fee of
            BDT 100. Bagela AB (Tellecto) delivery and payment condition apply for shipping.</small>
    </div> --}}
@endsection
