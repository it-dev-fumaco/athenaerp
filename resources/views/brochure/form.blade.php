<form action="/read_file" method="POST" enctype="multipart/form-data">
  @csrf
      <input type="file" name="file">
      <input type="submit" value="Read File">
    </form>
    