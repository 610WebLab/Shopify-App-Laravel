// var scriptTags = document.getElementsByTagName("script");
// for (var i = 0; i < scriptTags.length; i++) {
//     var scriptTag = scriptTags[i];
//     var src = scriptTag.getAttribute("src");
//     var url = 'https://shipping.webziainfotech.com/js/carrier_services.js?shop=' + Shopify.shop;

//     if (src === url) {
//         var scriptElement = document.createElement("script");
//         scriptElement.setAttribute("type", "text/javascript");
//         scriptElement.src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js";

//         scriptTag.parentNode.insertBefore(scriptElement, scriptTag);
//         break;
//     }
// }
// setTimeout(() => {
    // var styletag = document.createElement("link");
    // styletag.setAttribute("rel", "stylesheet");
    // styletag.href = "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css";
    // document.getElementsByTagName("head")[0].appendChild(styletag);
    // var styletag = document.createElement("link");
    // styletag.setAttribute("rel", "stylesheet");
    // styletag.href = "https://shipping.webziainfotech.com/css/shipping_rate.css";
    // document.getElementsByTagName("head")[0].appendChild(styletag);
    // var scripttag = document.createElement("script");
    // scripttag.setAttribute("type", "text/javascript");
    // scripttag.src = "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js";
    // document.getElementsByTagName("head")[0].appendChild(scripttag);
    // var scripttag = document.createElement("script");
    // scripttag.setAttribute("type", "text/javascript");
    // scripttag.src = "https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.js";
    // document.getElementsByTagName("head")[0].appendChild(scripttag);
    // if (window.location.href === shopUrl + '/cart') {
    //     var button = document.getElementsByClassName("cart__checkout-button")[0];
    //     var nextElement = button.nextElementSibling;
    //     if (nextElement == null) {
    //         var newDiv = document.createElement("div");
    //         newDiv.className = "tabal-rate-shipping-calculater";
    //         console.log("hi", newDiv)
    //         button.parentNode.insertBefore(newDiv, button.nextSibling);;
    //     } else if (nextElement.classList.contains("tabal-rate-shipping-calculater")) {
    //         console.log("Element with class exists.");
    //     }
    // }

    // setTimeout(() => {
    //     $(document).ready(function () {
    //         let html = `<div class="container" style="justify-content:end;">
    //             <div id="ship-overlay" style="display:block;">
    //                 <div class="cv-spinner">
    //                     <span class="spinner"></span>
    //                 </div>
    //             </div>
    //             <div class="blank_space"> </div>
    //             <div class="tbl-calculate-shipping-rates">
    //                 <h2>Cart Totals</h2>
    //                 <table>
    //                     <tbody>
    //                         <tr>
    //                             <th>Subtotal</th>
    //                             <td><span class="tbl-subtotal-price"></span></td>
    //                         </tr>
    //                         <tr>
    //                             <th>Shipping</th>
    //                             <td>
    //                             <p class="shipping-address"></p>
    //                                 <div class="table-shipping-rates">
    //                                 <ul class="list-table-rates"></ul>
    //                                 <input type="hidden" name="shippingInfo" id="shippingInfo" value=""/>
                                    
    //                                 </div>
    //                                 <button id="shipping-calculator-button">Change address</button>
    //                                 <div class="tbl-shipping-rates">
                                    
    //                                 </div>
    //                             </td>
    //                         </tr>
    //                         <tr>
    //                             <th>Tax</th>
    //                             <td>
    //                                 <div class="tbl-tax-price">
    //                                 </div>
    //                             </td>
    //                         </tr>
    //                         <tr>
    //                             <th>Total</th>
    //                             <td><div class="tbl-total-price"></div></td>
    //                         </tr>
    //                     </tbody>
    //                 </table>
    //             </div>
    //         </div>`;
    //         $("#ship-overlay").show();
    //         // $('.tabal-rate-shipping-calculater').html(html)

    //         $.ajax({
    //             url: "https://shipping.webziainfotech.com/shipping-options?shop=" + Shopify.shop,
    //             type: 'GET',
    //             dataType: 'json',
    //             async: false,
    //             success: function (response) {
    //                 if (response.status) {
    //                     if (response.ShippingOption.enable_shipping == 1) {
    //                         $('.tabal-rate-shipping-calculater').html(html);
    //                     }
    //                 }

    //             }
    //         });

    //         $.ajax({
    //             url: window.Shopify.routes.root + 'cart.js',
    //             type: 'GET',
    //             dataType: 'json', // added data type
    //             async: false,
    //             success: function (response) {
    //                 if (response != "") {
    //                     let quantity = response.items.length;

    //                     if (response.items_subtotal_price > 0) {
    //                         let _subTotal = Shopify.money_format + ' ' + ((response.items_subtotal_price / 100).toFixed(2));
    //                         let _totalPrice = Shopify.money_format + '' + ((response.original_total_price / 100).toFixed(2));
    //                         $('.tbl-subtotal-price').html(_subTotal);
    //                         $('.tbl-subtotal-price').attr('price', response.items_subtotal_price).attr("cart_quantity", response.item_count).attr("cart_weight", response.total_weight).attr("cart_lineitem", quantity)
    //                         $('.tbl-tax-price').html(Shopify.money_format + ' 0.00');
    //                         $('.tbl-total-price').html(_totalPrice);
    //                         $('.tabal-rate-shipping-calculater').css("display", "block").attr("token", response.token);
    //                         $("#ship-overlay").hide();

    //                     } else {
    //                         $('.tabal-rate-shipping-calculater').css("display", "none");
    //                     }
    //                 }

    //             }
    //         });
    //         // }
    //         getCountryAndState("https://shipping.webziainfotech.com/countryAndState?shop=" + Shopify.shop);
    //         function getCountryAndState(URL) {
    //             $("#ship-overlay").show();
    //             $.ajax({
    //                 url: URL,
    //                 type: 'GET',
    //                 dataType: 'json', // added data type
    //                 success: function (response) {
    //                     if (response.status) {
    //                         $("#ship-overlay").hide();
    //                         let countryOption = `<select class="tbl_country_state" name="tbl_country">`;
    //                         let stateOption = `<select class="tbl_state_data" name="tbl_state">`;
    //                         let country = "";
    //                         $.each(response.country, function (codeKey, Country) {
    //                             $.each(Country, function (nameKey, countryVal) {
    //                                 let selectContry = (codeKey == 'US') ? "selected" : "";
    //                                 country = `<option data-name ="` + nameKey + `" value="` + codeKey + `" selected="` + selectContry + `">` + nameKey + `</option>`;


    //                                 $.each(countryVal, function (statKey, stateVal) {
    //                                     if (stateVal.state_name) {
    //                                         stateOption += `<option data-name ="` + stateVal.state_name + `" value="` + stateVal.state_code + `">` + stateVal.state_name + `</option>`;
    //                                     } else {
    //                                         stateOption += `<option value="">Sate not avilable</option>`;
    //                                     }
    //                                 })
    //                             })
    //                             countryOption += country;

    //                         });
    //                         countryOption += country + `</select>`;
    //                         stateOption += `</select>`;

    //                         let formHtml = `<section class="tbl_shipping-calculator-form" style="display:none;">
    //                             <div class="tbl_country">`+ countryOption + `</div>
    //                             <div class="tbl_state">`+ stateOption + `</div>
    //                             <div class="tbl_city"><input type="text" placeholder="City" id="tbl_city_data" /></div>
    //                             <div class="tbl_post_code"><input type="text" placeholder="Postcode / ZIP" id="tbl_postCode" /></div>
    //                             <p>
    //                             <div class="table-rate-update"><button type="submit" name="calc_shipping" value="1" class="calc_shipping_rates">Update</button></div>
    //                             </p>
    //                         </section>`;
    //                         $('.tbl-shipping-rates').html(formHtml);
    //                     }
    //                 }
    //             })
    //         }

    //         let quantity = $('.tbl-subtotal-price').attr('cart_quantity')
    //         let weight = $('.tbl-subtotal-price').attr('cart_weight')
    //         let lineItem = $('.tbl-subtotal-price').attr('cart_lineitem')
    //         let price = $('.tbl-subtotal-price').attr('price')
    //         $.ajax({
    //             url: "https://shipping.webziainfotech.com/shippingRates?country=US&state=IN&zip=&item=" + quantity + "&weight=" + weight + "&line=" + lineItem + "&price=" + price,
    //             type: 'GET',
    //             dataType: 'json', // added data type
    //             async: false,
    //             success: function (response) {

    //                 let radioOption = "";
    //                 let address = 'United States (US)';

    //                 if (response !== "") {
    //                     $("#ship-overlay").hide();

    //                     if (response.flatRate) {
    //                         $.each(response.flatRate, function (index, flat) {
    //                             let price = (flat.shipPrice == 0) ? "Free Shipping" : Shopify.money_format + flat.shipPrice.toFixed(2);

    //                             radioOption += `<li>
    //                                         <label for="flat_`+ index + `">
    //                                             <input type="radio" id="flat_`+ index + `" class="tbl-shipping-options" data-price = "` + price + `" name="shipping_rates[0]" value="` + flat.shipPrice + `">`
    //                                 + flat.service_name + `: <strong>` + price + `</strong>
    //                                             </label>
    //                                         </li>`;
    //                         });
    //                     }


    //                     if (response.freeShips) {
    //                         $.each(response.freeShips, function (index, free) {
    //                             radioOption += `<li>
    //                                             <label for="free_`+ index + `">
    //                                                 <input type="radio" id="free_`+ index + `" class="tbl-shipping-options" data-price = "` + free.shipPrice + `" name="shipping_rates[0]" value="` + free.shipPrice + `">` + free.service_name + `
    //                                             </label>
    //                                         </li>`;
    //                         })
    //                     }

    //                     if (response.localPickup) {
    //                         $.each(response.localPickup, function (index, local) {
    //                             let price = (local.shipPrice == 0) ? "Free Shipping" : Shopify.money_format + local.shipPrice.toFixed(2);
    //                             radioOption += `<li>
    //                                         <label for="local_`+ index + `">
    //                                             <input type="radio" id="local_`+ index + `" class="tbl-shipping-options" data-price = "` + price + `" name="shipping_rates[0]" value="` + local.shipPrice + `">`
    //                                 + local.service_name + `: <strong>` + price + `</strong>
    //                                         </label>
    //                                     </li>`;
    //                         });
    //                     }

    //                     if (response.tableRate.length > 0) {
    //                         $.each(response.tableRate, function (index, table) {
    //                             $.each(table, function (key, element) {
    //                                 let price = (element.shipPrice == 0) ? "Free Shipping" : Shopify.money_format + element.shipPrice.toFixed(2);
    //                                 let desc = (element.description) ? element.description : element.service_name;
    //                                 radioOption += `<li>
    //                                         <label for="table_`+ key + `">
    //                                             <input type="radio" id="table_`+ index + `" class="tbl-shipping-options" data-price = "` + price + `" name="shipping_rates[0]" value="` + element.shipPrice + `">`
    //                                     + desc + `: <strong>` + price + `</strong>
    //                                         </label>
    //                                     </li>`;
    //                             })

    //                         });
    //                     }
    //                     $('.list-table-rates').html(radioOption);
    //                     $('.shipping-address').html(`Shipping to <strong>` + address + `</strong>`);


    //                 }

    //             }
    //         })

    //         setTimeout(() => {
    //             $('.tbl_country_state').select2();
    //             $('.tbl_state_data').select2();
    //             $('body').on('click', '#shipping-calculator-button', function (e) {
    //                 e.preventDefault();
    //                 $('select[name="tbl_country"]').select2().val("US").trigger('change');
    //                 $('.tbl_shipping-calculator-form').slideToggle();

    //             });

    //             $('select[name="tbl_country"]').change(function () {
    //                 let data = $(this).val();
    //                 $.ajax({
    //                     url: "https://shipping.webziainfotech.com/countryAndState?shop=" + Shopify.shop + "&country_code=" + data,
    //                     type: 'GET',
    //                     dataType: 'json', // added data type
    //                     success: function (response) {
    //                         if (response.status) {
    //                             let country1 = ``;
    //                             let state1 = '';
    //                             let countries1 = "";
    //                             $.each(response.country, function (codeKey, Country) {
    //                                 $.each(Country, function (nameKey, countryVal) {
    //                                     if (codeKey === data) {
    //                                         country1 = `<option data-name ="` + nameKey + `" value="` + codeKey + `" selected="selected">` + nameKey + `</option>`;
    //                                     } else {
    //                                         country1 += `<option data-name ="` + nameKey + `" value="` + codeKey + `">` + nameKey + `</option>`;
    //                                     }

    //                                     $.each(countryVal, function (statKey, stateVal) {
    //                                         if (stateVal.state_name != "") {
    //                                             state1 += `<option data-name ="` + stateVal.state_name + `" value="` + stateVal.state_code + `">` + stateVal.state_name + `</option>`;
    //                                         }
    //                                     })
    //                                 })
    //                                 countries1 += country1;

    //                             });

    //                             $('.tbl_country_state').html(countries1);
    //                             $('.tbl_state_data').html(state1);
    //                         }


    //                     }
    //                 })
    //             });

    //             $('.calc_shipping_rates').on('click', function (e) {
    //                 e.preventDefault();
    //                 $("#ship-overlay").show();

    //                 let country = $('.tbl_country_state').val();
    //                 let countryName = $('.tbl_country_state option:selected').attr('data-name');
    //                 let state = $('.tbl_state_data').val();
    //                 let stateName = $('.tbl_state_data option:selected').attr('data-name');
    //                 let city = $('#tbl_city_data').val();
    //                 let zip = $('#tbl_postCode').val();
    //                 let quantity = $('.tbl-subtotal-price').attr('cart_quantity')
    //                 let weight = $('.tbl-subtotal-price').attr('cart_weight')
    //                 let lineItem = $('.tbl-subtotal-price').attr('cart_lineitem')
    //                 let price = $('.tbl-subtotal-price').attr('price')
    //                 $.ajax({
    //                     url: "https://shipping.webziainfotech.com/shippingRates?country=" + country + "&state=" + state + "&zip=" + zip + "&item=" + quantity + "&weight=" + weight + "&line=" + lineItem + "&price=" + price,
    //                     type: 'GET',
    //                     dataType: 'json', // added data type
    //                     success: function (response) {

    //                         let radioOption = "";
    //                         let address = city + ' ' + zip + ', ' + stateName + ", " + countryName;

    //                         if (response !== "") {
    //                             $("#ship-overlay").hide();

    //                             if (response.flatRate) {
    //                                 $.each(response.flatRate, function (index, flat) {
    //                                     let price = (flat.shipPrice == 0) ? "Free Shipping" : Shopify.money_format + flat.shipPrice.toFixed(2);

    //                                     radioOption += `<li>
    //                                         <label for="flat_`+ index + `">
    //                                             <input type="radio" id="flat_`+ index + `" class="tbl-shipping-options" data-price = "` + price + `" name="shipping_rates[0]" value="` + flat.shipPrice + `">`
    //                                         + flat.service_name + `: <strong>` + price + `</strong>
    //                                             </label>
    //                                         </li>`;;
    //                                 });
    //                             }


    //                             if (response.freeShips) {
    //                                 $.each(response.freeShips, function (index, free) {
    //                                     radioOption += `<li>
    //                                             <label for="free_`+ index + `">
    //                                                 <input type="radio" id="free_`+ index + `" class="tbl-shipping-options" data-price = "` + free.shipPrice + `" name="shipping_rates[0]" value="` + free.shipPrice + `">` + free.service_name + `
    //                                             </label>
    //                                         </li>`;
    //                                 })
    //                             }

    //                             if (response.localPickup) {
    //                                 $.each(response.localPickup, function (index, local) {
    //                                     let price = (local.shipPrice == 0) ? "Free Shipping" : Shopify.money_format + local.shipPrice.toFixed(2);
    //                                     radioOption += `<li>
    //                                         <label for="local_`+ index + `">
    //                                             <input type="radio" id="local_`+ index + `" class="tbl-shipping-options" data-price = "` + price + `" name="shipping_rates[0]" value="` + local.shipPrice + `">`
    //                                         + local.service_name + `: <strong>` + price + `</strong>
    //                                         </label>
    //                                     </li>`;;
    //                                 });
    //                             }

    //                             if (response.tableRate.length > 0) {

    //                                 $.each(response.tableRate, function (index, table) {

    //                                     $.each(table, function (key, element) {


    //                                         let price = (element.shipPrice == 0) ? "Free Shipping" : Shopify.money_format + element.shipPrice.toFixed(2);
    //                                         let desc = (element.description) ? element.description : element.service_name;

    //                                         radioOption += `<li>
    //                                         <label for="table_`+ key + `">
    //                                             <input type="radio" id="table_`+ index + `" class="tbl-shipping-options" data-price = "` + price + `" name="shipping_rates[0]" value="` + element.shipPrice + `">`
    //                                             + desc + `: <strong>` + price + `</strong>
    //                                         </label>
    //                                     </li>`;
    //                                     })

    //                                 });
    //                             }
    //                             $('.list-table-rates').html(radioOption);
    //                             $('.shipping-address').html(`Shipping to <strong>` + address + `</strong>`);
    //                             $('.tbl_shipping-calculator-form').slideToggle();

    //                         }

    //                     }
    //                 })



    //             });

    //             $('body').on('change', 'input[name="shipping_rates[0]"]:radio', function (e) {
    //                 if ($(this).is(":checked")) {
    //                     let value = $(this).val();
    //                     let price = $('.tbl-subtotal-price').attr('price') / 100;
    //                     $('input[name="shippingInfo"]').val($(this).val());
    //                     let total = price + parseInt(value);
    //                     $('.tbl-total-price').html(Shopify.money_format + '' + (total.toFixed(2)));
    //                 }
    //             });

    //         }, 2000);



    //     });
    // }, 500);

// }, 500);
