function QuienesSomos() {
  return (
    <>
      {/* ===== BANNER ===== */}
      <section className="banner-contacto">
        QUIÉNES SOMOS
      </section>

      {/* ===== CONTENIDO ===== */}
      <section className="contenedor-contacto">
        <div className="card-contacto">

          <h2>Quiénes somos</h2>

          <p>
            Bellavista FC es un club dedicado a la formación deportiva de niños y jóvenes,
            enfocado en el desarrollo técnico, la disciplina y el trabajo en equipo.
          </p>

          <h3>Nuestra misión</h3>

          <p>
            Formar deportistas integrales con habilidades técnicas y valores sólidos.
          </p>

          <h3>Nuestra visión</h3>

          <p>
            Ser un referente en formación deportiva y desarrollo humano.
          </p>

        </div>
      </section>

      {/* ===== REDES ===== */}
      <section className="redes">
        <p>SÍGUENOS:</p>

        <div className="iconos">
          <span>📸</span>
          <span>📘</span>
          <span>▶</span>
          <span>𝕏</span>
          <span>🎵</span>
          <span>in</span>
        </div>
      </section>

      {/* ===== FOOTER ===== */}
      <footer className="footer">
        © 2026 Bellavista FC. Todos los derechos reservados.
      </footer>
    </>
  );
}

export default QuienesSomos;