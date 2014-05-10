//----- Information -----//
$(function(){
  $('#informationAlert').click(function(){
    $('#information .modal-title').html('this is head of information');
    $.ajax({
      type: 'POST',
      url: 'index.html',
      data: {id: '9999'}
    }).done(function(respone) {
      $('#information .modal-body').html(respone);
    });
    //$('#information').modal();
  });
});

/*/----- Maps dynamic height -----//
$(function(){
  $('#map_canvas').css({'height':($(window).width())/2.5});
  $(window).resize(function(){
      $('#map_canvas').css({'height':($(window).width())/2.5});
  });
});*/

//----- Switch option search menu -----//
$(function(){
  $('.filter').click(function(){
    $('#currentFilter').html($(this).text()+'&nbsp;<span class="caret"></span>');
    $('#currentFilter').attr('data-search',$(this).attr('data-search'));
  });
});

