jQuery( document ).ready( function( $ ) {
  $( '#product-voting-container' ).on( 'click', '.product-upvote, .product-downvote', function() {
    var postId = $( this ).data( 'post-id' );
    var action = $( this ).hasClass( 'product-upvote' ) ? 'product_upvote' : 'product_downvote';
    var nonce = productVoting.nonce;
    
    $.ajax( {
      url: productVoting.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: action,
        post_id: postId,
        nonce: nonce
      },
      success: function( response ) {
        if ( response.success ) {
          $( '#product-votes-' + postId ).html( response.data.votes );
        } else {
          alert( 'Error: ' + response.data );
        }
      },
      error: function( xhr, status, error ) {
        alert( 'Error: ' + xhr.responseText );
      }
    } );
  } );
} );
