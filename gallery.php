<?php include 'header.php'; ?>
<script>
  
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('bguser');
  });
</script>
<section>
  <h2>Cinema Gallery</h2>
  <p>Enjoy posters, events and behind-the-scenes moments.</p>
  <div class="cards">
  <?php
   
    $files = [
      "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO8ReaXaESB7yupAdF-xwy-vA_-MhgAY7Tew&s",
      "https://assets.mycast.io/posters/until-dawn-the-movie-fan-casting-poster-455949-large.jpg?1713017860",
      "https://i.discogs.com/_73kKavg332-3wOlrzdHqc_EFE3Bo28yg_yLwUOaRgY/rs:fit/g:sm/q:40/h:300/w:300/czM6Ly9kaXNjb2dz/LWRhdGFiYXNlLWlt/YWdlcy9SLTIxNTA3/NzYwLTE2NDA2NDMw/NjAtNjMwNS5wbmc.jpeg",
      "https://yt3.googleusercontent.com/ytc/AIdro_mR5hxJk__1FoP9QDKkeKGMYuSdhutr1UaMTwZZ8Cxj0g=s900-c-k-c0x00ffffff-no-rj",
      "https://www.shutterstock.com/image-vector/realistic-movie-night-advertising-poster-260nw-2209327217.jpg",
      "https://d1csarkz8obe9u.cloudfront.net/posterpreviews/movie-night-design-template-5cbd32bb8ddfe38aa34a3216d4e81c0c_screen.jpg?ts=1681419636",
      "https://d1csarkz8obe9u.cloudfront.net/posterpreviews/movie-night-flyer-template-054ccbb896881dcbbc6388d9c09b65ae_screen.jpg?ts=1636975221",
      "https://s3.amazonaws.com/thumbnails.venngage.com/template/a3414f73-e95b-4e97-b6ee-a6f9497f6d4b.png",
      "https://www.theatrestrust.org.uk/assets/000/000/522/Marlowe_Stage_detail.jpg?1494591189",
      "https://cdn-imgix.headout.com/media/images/563553a6986ed75659629ce90dbd871d-Theatre%20Generic_0000s_0003_AdobeStock_374285858.jpg?auto=format&q=90&fit=crop&ar=16%3A9&crop=faces",
      "https://media.gettyimages.com/id/971-73/video/wide-shot-backstage-area-of-newly-restored-theatre-vermont.jpg?s=640x640&k=20&c=RfknQBZZ26oUL6d1D0k-WKmgYZhOy1AHwTpqXDaS30Q=",
      "https://media.istockphoto.com/id/182850497/photo/theater-backstage.jpg?s=612x612&w=0&k=20&c=AFezfEzRMZW4f4eDaNH-idGFG9w8a9XxAvYhe8N5B8s=",
    ];

    foreach ($files as $link):
  ?>
    <div class="card"><img src="<?php echo $link; ?>" alt="gallery"></div>
  <?php endforeach;?>
</div>
</section>
<?php include 'footer.php'; ?>
