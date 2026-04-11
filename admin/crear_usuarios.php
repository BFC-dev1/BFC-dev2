<?php include('includes/header.php'); ?>

    <div class="col-md-12">
        </div class="card">
            </div class="card-header">
                <h4>
                    Agregar Usuarios
                    <a href="usuarios.php" class="btn btn-danger float-end">Atras</a>
                </h4>
            </div>
            <div class="card-body"></div>

                <form action="">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ingrese Nombre">
                    </div>
                    <div class="mb-3">
                        <label>Tipo de documento</label>
                        <input type="text" name="tipo_documento" class="form-control" placeholder="Ingrese Tipo de Documento">
                    </div>
                    <div class="mb-3">
                        <label>Documento</label>
                        <input type="text" name="documento" class="form-control" placeholder="Ingrese Documento">
                    </div>
                    <div class="mb-3">
                        <label>Número de Teléfono</label>
                        <input type="text" name="numero_telefono" class="form-control" placeholder="Ingrese Número de Teléfono">
                    </div>
                    <div class="mb-3">
                        <label>Correo</label>
                        <input type="text" name="correo" class="form-control" placeholder="Ingrese Correo">
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="text" name="password" class="form-control" placeholder="Ingrese Contraseña">
                    </div>
                    <div class="mb-3">
                        <label>Seleccionar Rol</label>
                        <select name="rol" class="form-select">
                            <option value="">Seleccionar Rol</option>
                            <option value="admin">Admin</option>
                            <option value="administrativo">Administrativo</option>
                            <option value="entrenador">Entrenador</option>
                            <option value="usuario">usuario</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Seleccionar Rol</label>
                        <br/>
                        <input type="checkbox" name="is_ban" style="width:30px;height:30px;" />
                    </div>
                    <div class="mb-3">
                        <button type="submit" name="saveUser" class="btn btn-primary">Guardar</button>




                    </div>                                                                                                                      
                </form>  
            </div>
        </div>
    </div>


<?php include('includes/footer.php'); ?>