<?php
if (empty($reservas)): ?>
    <tr><td colspan="10" style="text-align:center;padding:40px;color:#999;">No hay reservas registradas.</td></tr>
<?php else: ?>
    <?php foreach ($reservas as $r):
        $pagado = (float)$r['monto_pagado'];
        $saldo = max(0, (float)$r['monto_total'] - $pagado);
    ?>
    <tr>
        <td><strong><?= htmlspecialchars($r['codigo']) ?></strong></td>
        <td>
            <div style="font-weight:bold;color:var(--admin-blue);"><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></div>
            <div style="font-size:11px;color:#888;"><?= htmlspecialchars($r['email']) ?></div>
        </td>
        <td><?= htmlspecialchars($r['titulo'] ?? '-') ?></td>
        <td><?= date('d/m/Y', strtotime($r['fecha_viaje'])) ?></td>
        <td><strong>$<?= number_format($r['monto_total'], 2) ?></strong></td>
        <td class="col-pagado" style="color:#27ae60;font-weight:700;">$<?= number_format($pagado, 2) ?></td>
        <td class="col-saldo" style="color:#b8860b;font-weight:700;">$<?= number_format($saldo, 2) ?></td>
        <td class="col-metodo"><?= iconoMetodoHtml($r['metodo_pago']) ?></td>
        <td><?= $badge_estado[$r['estado']] ?? $r['estado'] ?></td>
        <td class="acciones">
            <a href="#" onclick="verDetalle(<?= $r['id'] ?>); return false;" title="Ver visualización del paquete"><i class="fas fa-eye"></i></a>
            <a href="reserva_ver.php?id=<?= $r['id'] ?>" title="Ver/Editar"><i class="fas fa-edit"></i></a>
            <a href="ver_pdf.php?id=<?= $r['id'] ?>" title="Imprimir PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
<?php if ($total_paginas > 1): ?>
<tr class="pagination-row"><td colspan="10">
    <div class="paginacion">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="#" class="page-link <?= $i === $pagina ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</td></tr>
<?php endif; ?>